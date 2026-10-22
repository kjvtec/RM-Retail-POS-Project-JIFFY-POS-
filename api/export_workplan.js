const fs = require('fs');
const path = require('path');
const JSZip = require('jszip');
const { calculate } = require('../workplan_metrics');
const model = require('../data/excel_row_map.json');

const templatePath = path.join(__dirname, '..', model.template);
const DAY = 24 * 60 * 60 * 1000;

function escapeXml(value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
}

function excelDate(value) {
  if (!value) return null;
  const time = Date.parse(`${String(value).slice(0, 10)}T00:00:00Z`);
  return Number.isFinite(time) ? time / DAY + 25569 : null;
}

function workDays(start, end) {
  if (!start || !end) return 0;
  let count = 0;
  for (let time = Date.parse(`${String(start).slice(0, 10)}T00:00:00Z`);
    time <= Date.parse(`${String(end).slice(0, 10)}T00:00:00Z`); time += DAY) {
    const day = new Date(time).getUTCDay();
    if (day !== 0 && day !== 6) count++;
  }
  return count;
}

function setCell(rowXml, ref, value, type = 'number', formula = null, style = null) {
  const start = rowXml.indexOf(`<c r="${ref}"`);
  if (start < 0) throw new Error(`Excel template is missing cell ${ref}`);
  const openEnd = rowXml.indexOf('>', start);
  const end = rowXml[openEnd - 1] === '/' ? openEnd + 1 : rowXml.indexOf('</c>', openEnd) + 4;
  const existing = rowXml.slice(start, end);
  let opening = existing.match(/^<c\b[^>]*?\/?\s*>/)[0].replace(/\s*\/?>$/, '');
  opening = opening.replace(/\s+t="[^"]*"/g, '');
  if (style !== null) opening = /\ss="[^"]*"/.test(opening)
    ? opening.replace(/\ss="[^"]*"/, ` s="${style}"`)
    : `${opening} s="${style}"`;
  if (type === 'text') opening += ' t="inlineStr"';
  if (type === 'formula-text') opening += ' t="str"';
  let content = '';
  if (formula) {
    content += `<f>${escapeXml(formula)}</f>`;
  } else if (type === 'number' || type === 'formula-text') {
    const oldFormula = existing.match(/<f\b[^>]*>[\s\S]*?<\/f>|<f\b[^>]*\/>/);
    if (oldFormula) content += oldFormula[0];
  }
  if (value !== null && value !== undefined && value !== '') {
    content += type === 'text'
      ? `<is><t xml:space="preserve">${escapeXml(value)}</t></is>`
      : `<v>${escapeXml(value)}</v>`;
  }
  return rowXml.slice(0, start) + (content ? `${opening}>${content}</c>` : `${opening}/>`)
    + rowXml.slice(end);
}

function progressStatus(planned, actual) {
  if (planned > actual + 0.000001) return 'Late';
  if (actual >= 1) return 'Complete';
  if (planned === 0 && actual === 0) return 'Not Started';
  return 'On Schedule';
}

async function buildWorkplanExcel(timeline, asOf) {
  const metrics = calculate(timeline.stages, model, asOf);
  if (!metrics) throw new Error('Excel progress cannot be calculated from the project data');
  const byRow = new Map();
  byRow.set(3, { kind: 'project', item: timeline.project });
  for (const stage of timeline.stages) {
    const stageRow = model.stages[String(stage.id)];
    if (!stageRow) throw new Error(`Milestone ${stage.id} is not present in the Excel reference template`);
    if (stageRow) byRow.set(Number(stageRow), { kind: 'stage', item: stage });
    for (const task of stage.tasks || []) {
      const taskRow = model.tasks[String(task.id)];
      if (!taskRow) throw new Error(`Task ${task.id} is not present in the Excel reference template`);
      if (taskRow) byRow.set(Number(taskRow), { kind: 'task', item: task });
    }
  }

  const zip = await JSZip.loadAsync(fs.readFileSync(templatePath));
  let sheet = await zip.file('xl/worksheets/sheet1.xml').async('string');
  sheet = sheet.replace(/<row r="(\d+)"[^>]*>[\s\S]*?<\/row>/g, rowXml => {
    const row = Number(rowXml.match(/^<row r="(\d+)"/)[1]);
    if (row === 1) {
      rowXml = setCell(rowXml, 'N1', 'As of date', 'text');
      rowXml = setCell(rowXml, 'O1', excelDate(asOf), 'number', null, 54);
      return rowXml;
    }
    const entry = byRow.get(row);
    if (!entry) return rowXml;
    const { item, kind } = entry;
    const progress = metrics.byRow[row];
    if (!progress) throw new Error(`Excel progress is missing for row ${row}`);
    const prefix = kind === 'stage' ? '   ' : kind === 'task' ? ' '.repeat(Math.min(12, 3 * (Number(item.level) || 2))) : '';
    rowXml = setCell(rowXml, `B${row}`, item.wbs || (kind === 'project' ? 0 : ''),
      /^\d+(?:\.\d+)?$/.test(String(item.wbs)) ? 'number' : 'text');
    rowXml = setCell(rowXml, `C${row}`, `${prefix}${item.name || item.title || ''}`, 'text');
    if (kind !== 'project') rowXml = setCell(rowXml, `D${row}`, item.assignee || '', 'text');
    rowXml = setCell(rowXml, `E${row}`, progress.planned);
    rowXml = setCell(rowXml, `F${row}`, progress.actual);
    const status = kind === 'project'
      ? metrics.planned > metrics.actual
        ? `Behind Plan ${Math.floor(metrics.planned) - Math.floor(metrics.actual)} pp`
        : 'On Plan'
      : progressStatus(progress.planned, progress.actual);
    rowXml = setCell(rowXml, `G${row}`, status, 'formula-text', kind === 'project'
      ? 'IF(E3>F3,"Behind Plan "&(ROUNDDOWN(E3*100,0)-ROUNDDOWN(F3*100,0))&" pp","On Plan")'
      : null);
    if (kind === 'project') {
      const starts = timeline.stages.map(stage => stage.start).filter(Boolean).sort();
      const ends = timeline.stages.map(stage => stage.end).filter(Boolean).sort();
      rowXml = setCell(rowXml, `I${row}`, excelDate(starts[0]));
      rowXml = setCell(rowXml, `J${row}`, excelDate(ends[ends.length - 1]));
    } else {
      rowXml = setCell(rowXml, `I${row}`, excelDate(item.start));
      rowXml = setCell(rowXml, `J${row}`, excelDate(item.end));
      if (kind === 'task') {
        rowXml = setCell(rowXml, `K${row}`, excelDate(item.actual_start));
        rowXml = setCell(rowXml, `L${row}`, excelDate(item.actual_finish));
        rowXml = setCell(rowXml, `M${row}`, item.remark || item.stuck_reason || '', 'text');
      }
    }
    if (row === 4) rowXml = rowXml.replace('AVERAGE(E5,,E30,E35,E93)', 'AVERAGE(E5,E30,E35,E93)');
    if (row === 239) {
      rowXml = setCell(rowXml, 'E239', progress.planned, 'number', 'AVERAGE(E240)');
      rowXml = setCell(rowXml, 'F239', progress.actual, 'number', 'AVERAGE(F240)');
    }
    if (row === 240) {
      rowXml = rowXml.replace('<c r="I240"', `<c r="H240" s="90" t="str"><f>NETWORKDAYS(I240,J240)&amp;" days"</f><v>${workDays(item.start, item.end)} days</v></c><c r="I240"`);
      rowXml = setCell(rowXml, 'B240', item.wbs, 'number', null, 47);
      rowXml = setCell(rowXml, 'C240', `      ${item.title}`, 'text', null, 80);
      rowXml = setCell(rowXml, 'D240', item.assignee || '', 'text', null, 39);
      rowXml = setCell(rowXml, 'E240', progress.planned, 'number',
        'MIN(1,MAX(F240,($O$1-I240+1)/(J240-I240+1)))', 16);
      rowXml = setCell(rowXml, 'F240', progress.actual, 'number', null, 16);
      rowXml = setCell(rowXml, 'G240', status, 'formula-text',
        'IF(E240>F240,"Late",IF(F240=100%,"Complete",IF(E240=0%,"Not Started","On Schedule")))', 74);
      rowXml = setCell(rowXml, 'I240', excelDate(item.start), 'number', null, 56);
      rowXml = setCell(rowXml, 'J240', excelDate(item.end), 'number', null, 56);
    }
    return rowXml;
  });
  sheet = sheet.replace(/TODAY\(\)/g, '$O$1').replace(/ref="A2:O239"/g, 'ref="A2:O240"');
  zip.file('xl/worksheets/sheet1.xml', sheet);
  let workbook = await zip.file('xl/workbook.xml').async('string');
  workbook = workbook.replace(/<calcPr\b[^>]*\/>/, '<calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/>');
  zip.file('xl/workbook.xml', workbook);
  return zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
}

module.exports = { buildWorkplanExcel };
