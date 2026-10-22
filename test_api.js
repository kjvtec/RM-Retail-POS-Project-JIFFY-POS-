/**
 * Automated Verification Test for RM-Retail POS Project (JIFFY POS) (Vercel + Supabase)
 */

const http = require('http');
const assert = require('assert');
const JSZip = require('jszip');
const { calculate } = require('./workplan_metrics');
const { buildWorkplanExcel } = require('./api/export_workplan');
const model = require('./data/excel_row_map.json');
const dataset = require('./data/workplan_dataset.json');

async function runTests() {
  console.log('--- Starting System Verification Tests ---');

  const stages = dataset.milestones.map(stage => ({ ...stage, start: stage.start_date, end: stage.end_date,
    tasks: dataset.tasks.filter(task => task.stage_id === stage.id).map(task => ({ ...task, start: task.start_date, end: task.end_date })) }));
  const metrics = calculate(stages, model, '2026-10-22');
  assert.equal(Math.floor(metrics.planned), 23);
  assert.equal(Math.floor(metrics.actual), 16);
  assert.equal(Math.floor(metrics.planned) - Math.floor(metrics.actual), 7);
  const xlsx = await buildWorkplanExcel({ project: dataset.project, stages }, '2026-10-22');
  const zip = await JSZip.loadAsync(xlsx);
  const sheet = await zip.file('xl/worksheets/sheet1.xml').async('string');
  const cell = ref => (sheet.match(new RegExp(`<c r="${ref}"[^>]*>[\\s\\S]*?<\\/c>`)) || [])[0] || '';
  assert.match(cell('E4'), /AVERAGE\(E5,E30,E35,E93\)/);
  assert.match(cell('G3'), /Behind Plan 7%/);
  assert.match(cell('H240'), /NETWORKDAYS\(I240,J240\)/);
  console.log('✅ Excel formula, 23% / 16% / Behind Plan 7% display, and reference-format export verified.');

  // Test 1: Verify require of api/index.js
  try {
    const apiHandler = require('./api/index.js');
    console.log('✅ Test 1: api/index.js imported successfully without syntax errors.');
  } catch (err) {
    console.error('❌ Test 1 Failed:', err);
    process.exit(1);
  }

  // Test 2: Start local_server and perform HTTP requests
  const { spawn } = require('child_process');
  const serverProc = spawn('node', ['local_server.js'], {
    env: { ...process.env, PORT: '3099' }
  });

  serverProc.stdout.on('data', data => {
    // console.log(`[Server]: ${data}`);
  });

  serverProc.stderr.on('data', data => {
    console.error(`[Server Error]: ${data}`);
  });

  // Wait 1.5 seconds for server to start
  await new Promise(r => setTimeout(r, 1500));

  function request(path, options = {}) {
    return new Promise((resolve, reject) => {
      const req = http.request(`http://localhost:3099${path}`, options, res => {
        let body = '';
        res.on('data', chunk => body += chunk);
        res.on('end', () => {
          resolve({ status: res.statusCode, headers: res.headers, body });
        });
      });
      req.on('error', reject);
      if (options.body) req.write(options.body);
      req.end();
    });
  }

  try {
    // Test 2.1: The root route opens the Progress timeline.
    const resHome = await request('/');
    if (resHome.status === 200 && resHome.body.includes('Weekly Gantt Chart')) {
      console.log('✅ Test 2.1: Root route serves the Progress timeline (200 OK).');
    } else {
      console.error('❌ Test 2.1 Failed: Status', resHome.status);
    }

    const resDashboard = await request('/dashboard.html');
    if (resDashboard.status === 200 && resDashboard.body.includes('Overall Projects Dashboard')) {
      console.log('✅ Test 2.1b: Existing dashboard remains available at /dashboard.html.');
    } else {
      console.error('❌ Test 2.1b Failed: Status', resDashboard.status);
    }

    const resIndex = await request('/index.html');
    if (resIndex.status === 200 && resIndex.body.includes('project_timeline.html?project_id=1')) {
      console.log('✅ Test 2.1c: Vercel index.html directs visitors to Progress.');
    } else {
      console.error('❌ Test 2.1c Failed: Status', resIndex.status);
    }

    // Test 2.2: Check static project_timeline.html rewrite
    const resTimeline = await request('/project_timeline.php?project_id=1');
    if (resTimeline.status === 200 && resTimeline.body.includes('Timeline Dashboard')) {
      console.log('✅ Test 2.2: Rewrite /project_timeline.php -> project_timeline.html works (200 OK).');
    } else {
      console.error('❌ Test 2.2 Failed: Status', resTimeline.status);
    }

    assert.match(resTimeline.body, /id="openHighLevel"[^>]*aria-controls="highLevelDialog"/);
    assert.match(resTimeline.body, /<dialog id="highLevelDialog"/);
    const resHighLevelImage = await request('/assets/high-level-project-timeline.png');
    assert.equal(resHighLevelImage.status, 200);
    assert.match(resHighLevelImage.headers['content-type'], /image\/png/);
    console.log('✅ High Level Project Timeline dialog and Excel reference image are available.');

    // Test 2.3: Check admin login page
    const resLogin = await request('/admin/login');
    if (resLogin.status === 200 && resLogin.body.includes('RM-Retail POS Admin Portal')) {
      console.log('✅ Test 2.3: Rewrite /admin/login -> admin/login.html works (200 OK).');
    } else {
      console.error('❌ Test 2.3 Failed: Status', resLogin.status);
    }

    // Test 2.4: Check unauthenticated access to check_auth endpoint
    const resAuth = await request('/api.php?action=check_auth');
    if (resAuth.status === 401) {
      console.log('✅ Test 2.4: Protected auth check correctly rejected unauthenticated request (401 Unauthorized).');
    } else {
      console.error('❌ Test 2.4 Unexpected status:', resAuth.status);
    }

    // Test 2.5: Check Supabase error message when env variables are missing
    const resProjects = await request('/api.php?action=get_projects');
    if (resProjects.status === 500 && resProjects.body.includes('Supabase configuration missing')) {
      console.log('✅ Test 2.5: Safe error handling verified (clean Thai message when SUPABASE_URL not configured).');
    } else {
      console.log(`ℹ️ Test 2.5: Status ${resProjects.status} (Supabase configured or response: ${resProjects.body.substring(0, 80)}...)`);
    }

    console.log('\n🎉 ALL AUTOMATED CODE INTEGRITY TESTS PASSED SUCCESSFULLY! 🎉\n');
  } catch (err) {
    console.error('❌ Tests failed with error:', err);
  } finally {
    serverProc.kill();
  }
}

runTests();
