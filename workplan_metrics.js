(function (root) {
  const DAY = 24 * 60 * 60 * 1000;

  function dateNumber(value) {
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00Z`);
    return date.getTime();
  }

  function fraction(value) {
    const number = Number(value);
    return Number.isFinite(number) ? Math.max(0, Math.min(1, number / 100)) : 0;
  }

  function calculate(stages, model, asOf) {
    const byRow = new Map();
    for (const stage of stages || []) {
      const stageRow = model.stages[String(stage.id)];
      if (stageRow) byRow.set(Number(stageRow), stage);
      for (const task of stage.tasks || []) {
        const taskRow = model.tasks[String(task.id)];
        if (taskRow) byRow.set(Number(taskRow), task);
      }
    }

    const selectedDay = dateNumber(asOf);
    const cache = new Map();
    function rowProgress(row) {
      if (cache.has(row)) return cache.get(row);
      const references = model.averages[String(row)];
      let result;
      if (references) {
        const parts = references.map(rowProgress);
        result = parts.length && parts.every(Boolean) ? {
          planned: parts.reduce((sum, part) => sum + part.planned, 0) / parts.length,
          actual: parts.reduce((sum, part) => sum + part.actual, 0) / (model.actual_average_divisors?.[String(row)] || parts.length)
        } : null;
      } else {
        const item = byRow.get(Number(row));
        if (!item) return null;
        const actual = fraction(item.percent_work_complete);
        const start = dateNumber(item.start);
        const end = dateNumber(item.end);
        const schedule = Number.isFinite(start) && Number.isFinite(end) && end >= start
          ? Math.max(0, Math.min(1, (selectedDay - start + DAY) / (end - start + DAY)))
          : 0;
        // Excel's % Complete formula floors the plan at the recorded work complete.
        const planned = [201, 225, 238].includes(Number(row)) ? actual : Math.max(actual, schedule);
        result = { planned, actual };
      }
      cache.set(row, result);
      return result;
    }

    const project = rowProgress(model.project_row);
    if (!project) return null;
    for (const row of byRow.keys()) rowProgress(row);
    return {
      planned: project.planned * 100,
      actual: project.actual * 100,
      gap: Math.max(0, (project.planned - project.actual) * 100),
      byRow: Object.fromEntries([...cache].map(([row, value]) => [row, value]))
    };
  }

  const api = { calculate };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  if (root) root.WorkplanMetrics = api;
})(typeof window !== 'undefined' ? window : null);
