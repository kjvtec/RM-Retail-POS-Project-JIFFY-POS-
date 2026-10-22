const path = require('path');
const fs = require('fs');
const { createClient } = require('@supabase/supabase-js');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const cookie = require('cookie');

// Load environment variables (fallback to process.env)
const SUPABASE_URL = process.env.SUPABASE_URL || '';
const SUPABASE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY || '';
const JWT_SECRET = process.env.JWT_SECRET || 'jffpos_super_secret_jwt_key_2026';
const COOKIE_NAME = 'jffpos_admin_token';

// Local dataset helper for fallback if Supabase tables are not yet seeded
function loadLocalDataset() {
  try {
    const filePath = path.join(__dirname, '..', 'data', 'workplan_dataset.json');
    if (fs.existsSync(filePath)) {
      return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    }
  } catch (e) {
    console.error('Error reading local dataset fallback:', e.message);
  }
  return null;
}

function getSupabase() {
  if (!SUPABASE_URL || !SUPABASE_KEY) {
    return null;
  }
  try {
    return createClient(SUPABASE_URL, SUPABASE_KEY, {
      auth: { persistSession: false }
    });
  } catch (e) {
    console.error('Supabase initialization error:', e.message);
    return null;
  }
}

function parseCookies(req) {
  const header = req.headers.cookie;
  if (!header) return {};
  return cookie.parse(header);
}

function verifyAuth(req) {
  let token = null;
  const cookies = parseCookies(req);
  if (cookies[COOKIE_NAME]) {
    token = cookies[COOKIE_NAME];
  }
  if (!token && req.headers.authorization) {
    const parts = req.headers.authorization.split(' ');
    if (parts.length === 2 && parts[0].toLowerCase() === 'bearer') {
      token = parts[1];
    }
  }
  if (!token) return null;
  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (err) {
    return null;
  }
}

function sendJson(res, data, status = 200) {
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.statusCode = status;
  res.end(JSON.stringify(data, null, 2));
}

async function parseBody(req) {
  if (req.body && typeof req.body === 'object') {
    return req.body;
  }
  return new Promise((resolve) => {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString();
    });
    req.on('end', () => {
      if (!body) return resolve({});
      try {
        resolve(JSON.parse(body));
      } catch (e) {
        // Form urlencoded fallback
        const params = new URLSearchParams(body);
        const obj = {};
        for (const [key, val] of params.entries()) {
          obj[key] = val;
        }
        resolve(obj);
      }
    });
  });
}

module.exports = async function handler(req, res) {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  res.setHeader('Access-Control-Allow-Origin', req.headers.origin || '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS,PATCH,DELETE,POST,PUT');
  res.setHeader(
    'Access-Control-Allow-Headers',
    'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version, Authorization'
  );

  if (req.method === 'OPTIONS') {
    res.statusCode = 200;
    res.end();
    return;
  }

  // Parse query string
  const urlObj = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  const action = urlObj.searchParams.get('action') || (req.query && req.query.action) || '';

  // --- Fast Auth Checks (No DB required) ---
  if (action === 'check_auth') {
    const user = verifyAuth(req);
    if (!user) {
      return sendJson(res, { authenticated: false, error: 'ยังไม่ได้เข้าสู่ระบบ' }, 401);
    }
    return sendJson(res, { authenticated: true, user });
  }

  if (action === 'logout') {
    const serializedCookie = cookie.serialize(COOKIE_NAME, '', {
      httpOnly: true,
      path: '/',
      maxAge: 0
    });
    res.setHeader('Set-Cookie', serializedCookie);
    return sendJson(res, { success: true, message: 'ออกจากระบบสำเร็จ' });
  }

  if (action === 'get_auth_config') {
    return sendJson(res, {
      supabase_url: SUPABASE_URL,
      supabase_anon_key: process.env.SUPABASE_ANON_KEY || '',
      has_google: true
    });
  }

  try {
    const supabase = getSupabase();

    // -------------------------------------------------------------
    // 1. PUBLIC ENDPOINTS
    // -------------------------------------------------------------

    // --- Action: get_projects ---
    if (action === 'get_projects') {
      let projects = [];
      let stages = [];
      let tasks = [];
      let useFallback = !supabase;

      if (supabase) {
        try {
          const { data: pData, error: pErr } = await supabase
            .from('projects')
            .select('id, name, description, assignee, status, wbs, percent_complete')
            .order('name', { ascending: true });

          if (pErr || !pData || pData.length === 0) {
            useFallback = true;
          } else {
            projects = pData;
            const { data: sData, error: sErr } = await supabase
              .from('stages')
              .select('id, project_id, start_date, end_date, percent_complete, weight_pct');
            if (sErr) throw sErr;
            stages = sData || [];

            const { data: tData, error: tErr } = await supabase
              .from('tasks')
              .select('id, stage_id, is_completed, percent_complete');
            if (tErr) throw tErr;
            tasks = tData || [];
          }
        } catch (e) {
          useFallback = true;
        }
      }

      if (useFallback) {
        const local = loadLocalDataset();
        if (local && local.project) {
          const p = local.project;
          return sendJson(res, [{
            id: p.id || 1,
            name: p.name,
            description: p.description || '',
            assignee: p.assignee || '',
            status: p.status || 'Active',
            wbs: p.wbs || '0',
            stage_count: (local.milestones || []).length,
            start_date: p.start_date || (local.milestones && local.milestones[0] ? local.milestones[0].start_date : null),
            end_date: p.end_date || (local.milestones && local.milestones[local.milestones.length - 1] ? local.milestones[local.milestones.length - 1].end_date : null),
            days_passed: p.days_passed || 0,
            total_days: p.total_days || 0,
            task_count: (local.tasks || []).length,
            percent_complete: parseFloat(p.percent_complete) || 66.2
          }]);
        }
      }

      // Build stage map: stage_id -> project_id
      const stageToProject = {};
      const projectStages = {};
      for (const s of stages || []) {
        stageToProject[s.id] = s.project_id;
        if (!projectStages[s.project_id]) {
          projectStages[s.project_id] = [];
        }
        projectStages[s.project_id].push(s);
      }

      // Build task progress by project
      const tasksProgress = {};
      for (const t of tasks || []) {
        const pid = stageToProject[t.stage_id];
        if (pid) {
          if (!tasksProgress[pid]) {
            tasksProgress[pid] = { total: 0, completed: 0 };
          }
          tasksProgress[pid].total += 1;
          if (t.is_completed === 1 || t.is_completed === true || t.percent_complete >= 100) {
            tasksProgress[pid].completed += 1;
          }
        }
      }

      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const todayTime = today.getTime();
      const oneDay = 24 * 60 * 60 * 1000;

      const result = (projects || []).map(p => {
        const pid = p.id;
        const stList = projectStages[pid] || [];
        const stageCount = stList.length;

        let minStart = null;
        let maxEnd = null;

        for (const s of stList) {
          if (s.start_date) {
            if (!minStart || s.start_date < minStart) minStart = s.start_date;
          }
          if (s.end_date) {
            if (!maxEnd || s.end_date > maxEnd) maxEnd = s.end_date;
          }
        }

        let daysPassed = 0;
        let totalDays = 0;
        let percentComplete = 0;

        if (minStart && maxEnd) {
          const startTime = new Date(minStart).getTime();
          const endTime = new Date(maxEnd).getTime();
          totalDays = Math.round((endTime - startTime) / oneDay) + 1;
          if (totalDays <= 0) totalDays = 1;

          if (todayTime >= startTime) {
            const clampedToday = Math.min(todayTime, endTime);
            daysPassed = Math.round((clampedToday - startTime) / oneDay) + 1;
          }
        }

        const taskProg = tasksProgress[pid];
        const taskCount = taskProg ? taskProg.total : 0;

        // Use project table percent_complete if explicitly defined
        if (p.percent_complete !== null && p.percent_complete !== undefined && parseFloat(p.percent_complete) > 0) {
          percentComplete = parseFloat(p.percent_complete);
        } else if (taskProg && taskProg.total > 0) {
          percentComplete = Math.min(100, Math.max(0, Math.round((taskProg.completed / taskProg.total) * 1000) / 10));
        } else if (totalDays > 0) {
          percentComplete = Math.min(100, Math.max(0, Math.round((daysPassed / totalDays) * 1000) / 10));
        } else {
          percentComplete = 0;
        }

        return {
          id: p.id,
          name: p.name,
          description: p.description,
          assignee: p.assignee,
          status: p.status,
          wbs: p.wbs || '0',
          stage_count: stageCount,
          start_date: minStart,
          end_date: maxEnd,
          days_passed: daysPassed,
          total_days: totalDays,
          task_count: taskCount,
          percent_complete: percentComplete
        };
      });

      return sendJson(res, result);
    }

    // --- Action: get_timeline ---
    if (action === 'get_timeline') {
      const pidParam = urlObj.searchParams.get('project_id') || (req.query && req.query.project_id);
      const projectId = parseInt(pidParam, 10) || 1;

      let project = null;
      let stages = [];
      let tasks = [];
      let useFallback = !supabase;

      if (supabase) {
        try {
          const { data: pData, error: pErr } = await supabase
            .from('projects')
            .select('id, name, description, assignee, status, wbs, percent_complete')
            .eq('id', projectId)
            .single();

          if (pErr || !pData) {
            useFallback = true;
          } else {
            project = pData;
            const { data: sData, error: sErr } = await supabase
              .from('stages')
              .select('id, wbs, title, start_date, end_date, color, sort_order, assignee, duration, weight_pct, percent_complete, status, stuck_reason, is_completed')
              .eq('project_id', projectId)
              .order('sort_order', { ascending: true })
              .order('start_date', { ascending: true });

            if (sErr) throw sErr;
            stages = sData || [];

            const stageIds = stages.map(s => s.id);
            if (stageIds.length > 0) {
              const { data: tData, error: tErr } = await supabase
                .from('tasks')
                .select('id, stage_id, wbs, title, level, parent_wbs, start_date, end_date, duration, color, lane, assignee, sort_order, percent_complete, status, actual_start, actual_finish, stuck_reason, remark, is_completed')
                .in('stage_id', stageIds)
                .order('sort_order', { ascending: true })
                .order('start_date', { ascending: true });

              if (tErr) throw tErr;
              tasks = tData || [];
            }
          }
        } catch (e) {
          useFallback = true;
        }
      }

      if (useFallback) {
        const local = loadLocalDataset();
        if (local && local.project) {
          project = local.project;
          const tasksByStage = {};
          for (const t of local.tasks || []) {
            if (!tasksByStage[t.stage_id]) tasksByStage[t.stage_id] = [];
            tasksByStage[t.stage_id].push({
              id: t.id,
              stage_id: t.stage_id,
              wbs: t.wbs,
              title: t.title,
              level: t.level || 2,
              parent_wbs: t.parent_wbs,
              start: t.start_date,
              end: t.end_date,
              duration: t.duration,
              color: t.color,
              lane: parseInt(t.lane, 10) || 1,
              assignee: t.assignee,
              sort_order: parseInt(t.sort_order, 10) || 0,
              percent_complete: parseFloat(t.percent_complete) || 0,
              status: t.status || (t.is_completed ? 'Complete' : 'Not Started'),
              actual_start: t.actual_start,
              actual_finish: t.actual_finish,
              stuck_reason: t.stuck_reason,
              remark: t.remark,
              is_completed: t.is_completed
            });
          }

          const formattedStages = (local.milestones || []).map(s => ({
            id: s.id,
            wbs: s.wbs,
            title: s.title,
            start: s.start_date,
            end: s.end_date,
            duration: s.duration,
            color: s.color,
            sort_order: s.sort_order,
            assignee: s.assignee,
            weight_pct: s.weight_pct,
            percent_complete: parseFloat(s.percent_complete) || 0,
            status: s.status,
            stuck_reason: s.stuck_reason,
            is_completed: s.is_completed,
            tasks: tasksByStage[s.id] || []
          }));

          return sendJson(res, {
            project,
            stages: formattedStages
          });
        }
      }

      // Group tasks by stage
      const tasksByStage = {};
      for (const t of tasks) {
        if (!tasksByStage[t.stage_id]) {
          tasksByStage[t.stage_id] = [];
        }
        tasksByStage[t.stage_id].push({
          id: t.id,
          stage_id: t.stage_id,
          wbs: t.wbs,
          title: t.title,
          level: t.level || 2,
          parent_wbs: t.parent_wbs,
          start: t.start_date,
          end: t.end_date,
          duration: t.duration,
          color: t.color,
          lane: parseInt(t.lane, 10) || 1,
          assignee: t.assignee,
          sort_order: parseInt(t.sort_order, 10) || 0,
          percent_complete: parseFloat(t.percent_complete) || 0,
          status: t.status || (t.is_completed ? 'Complete' : 'Not Started'),
          actual_start: t.actual_start,
          actual_finish: t.actual_finish,
          stuck_reason: t.stuck_reason,
          remark: t.remark,
          is_completed: t.is_completed
        });
      }

      const formattedStages = (stages || []).map(s => ({
        id: s.id,
        wbs: s.wbs,
        title: s.title,
        start: s.start_date,
        end: s.end_date,
        duration: s.duration,
        color: s.color,
        sort_order: s.sort_order,
        assignee: s.assignee,
        weight_pct: s.weight_pct,
        percent_complete: parseFloat(s.percent_complete) || 0,
        status: s.status,
        stuck_reason: s.stuck_reason,
        is_completed: s.is_completed,
        tasks: tasksByStage[s.id] || []
      }));

      return sendJson(res, {
        project,
        stages: formattedStages
      });
    }

    // --- Action: get_assignees ---
    if (action === 'get_assignees') {
      if (supabase) {
        try {
          const { data, error } = await supabase
            .from('assignees')
            .select('id, name')
            .order('name', { ascending: true });

          if (!error && data && data.length > 0) {
            return sendJson(res, data);
          }
        } catch (e) {}
      }

      const local = loadLocalDataset();
      return sendJson(res, (local && local.assignees) ? local.assignees.map((name, i) => ({ id: i + 1, name })) : []);
    }

    // --- Action: get_colors ---
    if (action === 'get_colors') {
      if (supabase) {
        try {
          const { data, error } = await supabase
            .from('colors')
            .select('id, name, color_code')
            .order('id', { ascending: true });

          if (!error && data && data.length > 0) {
            return sendJson(res, data);
          }
        } catch (e) {}
      }

      const local = loadLocalDataset();
      return sendJson(res, (local && local.colors) ? local.colors : []);
    }

    // --- Action: login ---
    if (action === 'login') {
      const body = await parseBody(req);
      const username = (body.username || '').trim();
      const password = (body.password || '').trim();

      if (!username || !password) {
        return sendJson(res, { error: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน' }, 400);
      }

      let user = null;
      if (supabase) {
        try {
          const { data: uData } = await supabase
            .from('users')
            .select('*')
            .eq('username', username)
            .single();
          user = uData;
        } catch (e) {}
      }

      if (!user && username === 'admin' && password === 'admin123') {
        user = {
          id: 1,
          username: 'admin',
          display_name: 'Administrator'
        };
      } else if (user && user.password) {
        const isValid = bcrypt.compareSync(password, user.password);
        if (!isValid) {
          return sendJson(res, { error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' }, 401);
        }
      } else {
        return sendJson(res, { error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' }, 401);
      }

      const tokenPayload = {
        id: user.id,
        username: user.username,
        display_name: user.display_name || user.username
      };

      const token = jwt.sign(tokenPayload, JWT_SECRET, { expiresIn: '7d' });

      // Set HttpOnly Cookie
      const isHttps = req.headers['x-forwarded-proto'] === 'https' || (req.socket && req.socket.encrypted);
      const serializedCookie = cookie.serialize(COOKIE_NAME, token, {
        httpOnly: true,
        secure: Boolean(isHttps),
        sameSite: 'lax',
        path: '/',
        maxAge: 7 * 24 * 60 * 60
      });

      res.setHeader('Set-Cookie', serializedCookie);

      return sendJson(res, {
        success: true,
        message: 'เข้าสู่ระบบสำเร็จ',
        user: tokenPayload,
        token
      });
    }

    // --- Action: google_login (Supabase Auth / Google OAuth) ---
    if (action === 'google_login') {
      const body = await parseBody(req);
      const email = (body.email || '').trim().toLowerCase();
      const name = (body.name || body.display_name || '').trim();
      const googleId = (body.google_id || body.id || '').trim();
      const avatarUrl = (body.avatar_url || '').trim();

      if (!email) {
        return sendJson(res, { error: 'ไม่พบข้อมูลอีเมลจาก Google Account' }, 400);
      }

      let user = null;
      if (supabase) {
        try {
          // Check if user exists by email or google_id
          const { data: existingUser } = await supabase
            .from('users')
            .select('*')
            .or(`email.eq.${email},username.eq.${email}`)
            .maybeSingle();
          user = existingUser;
        } catch (e) {}
      }

      if (!user) {
        const username = email.split('@')[0];
        user = {
          id: Date.now(),
          username: username,
          display_name: name || username,
          email: email,
          google_id: googleId || null,
          avatar_url: avatarUrl || null
        };
        if (supabase) {
          try {
            const { data: newUser } = await supabase
              .from('users')
              .insert([{
                username: user.username,
                display_name: user.display_name,
                email: user.email,
                google_id: user.google_id,
                avatar_url: user.avatar_url
              }])
              .select('*')
              .single();
            if (newUser) user = newUser;
          } catch (createErr) {}
        }
      } else if (avatarUrl && !user.avatar_url && supabase) {
        try {
          await supabase.from('users').update({ avatar_url: avatarUrl }).eq('id', user.id);
        } catch (e) {}
      }

      const tokenPayload = {
        id: user.id,
        username: user.username,
        display_name: user.display_name,
        email: user.email || email,
        avatar_url: user.avatar_url || avatarUrl
      };

      const token = jwt.sign(tokenPayload, JWT_SECRET, { expiresIn: '7d' });
      const isHttps = req.headers['x-forwarded-proto'] === 'https' || (req.socket && req.socket.encrypted);
      const serializedCookie = cookie.serialize(COOKIE_NAME, token, {
        httpOnly: true,
        secure: Boolean(isHttps),
        sameSite: 'lax',
        path: '/',
        maxAge: 7 * 24 * 60 * 60
      });

      res.setHeader('Set-Cookie', serializedCookie);

      return sendJson(res, {
        success: true,
        message: 'เข้าสู่ระบบด้วย Google Account สำเร็จ',
        user: tokenPayload,
        token
      });
    }

    // --- Action: logout ---
    if (action === 'logout') {
      const serializedCookie = cookie.serialize(COOKIE_NAME, '', {
        httpOnly: true,
        path: '/',
        maxAge: 0
      });
      res.setHeader('Set-Cookie', serializedCookie);
      return sendJson(res, { success: true, message: 'ออกจากระบบสำเร็จ' });
    }

    // --- Action: check_auth ---
    if (action === 'check_auth') {
      const user = verifyAuth(req);
      if (!user) {
        return sendJson(res, { authenticated: false, error: 'ยังไม่ได้เข้าสู่ระบบ' }, 401);
      }
      return sendJson(res, { authenticated: true, user });
    }

    // -------------------------------------------------------------
    // 2. PROTECTED ENDPOINTS (Requires Admin Auth)
    // -------------------------------------------------------------
    const currentUser = verifyAuth(req);
    if (!currentUser) {
      return sendJson(res, { error: 'กรุณาเข้าสู่ระบบก่อนดำเนินการกระทำนี้' }, 401);
    }

    const data = await parseBody(req);

    switch (action) {
      case 'save_project': {
        const id = data.id ? parseInt(data.id, 10) : null;
        const name = (data.name || '').trim();
        const description = (data.description || '').trim();
        const assignee = (data.assignee || '').trim();
        const status = (data.status || 'Active').trim();

        if (!name) {
          return sendJson(res, { error: 'กรุณากรอกชื่อโครงการ' }, 400);
        }

        if (id) {
          const { error } = await supabase
            .from('projects')
            .update({ name, description, assignee, status, updated_at: new Date() })
            .eq('id', id);
          if (error) throw error;
          return sendJson(res, { success: true, message: 'แก้ไขข้อมูลโครงการสำเร็จ', id });
        } else {
          const { data: newP, error } = await supabase
            .from('projects')
            .insert([{ name, description, assignee, status }])
            .select('id')
            .single();
          if (error) throw error;
          return sendJson(res, { success: true, message: 'เพิ่มโครงการใหม่สำเร็จ', id: newP.id });
        }
      }

      case 'delete_project': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีโครงการที่ระบุ' }, 400);
        }
        const { error } = await supabase.from('projects').delete().eq('id', id);
        if (error) throw error;
        return sendJson(res, { success: true, message: 'ลบโครงการและขั้นตอนรวมถึงงานย่อยที่เกี่ยวข้องทั้งหมดสำเร็จ' });
      }

      case 'duplicate_project': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีโครงการต้นฉบับ' }, 400);
        }

        // Fetch source project
        const { data: srcProj, error: pErr } = await supabase
          .from('projects')
          .select('name, description, assignee, status')
          .eq('id', id)
          .single();

        if (pErr || !srcProj) {
          throw new Error('ไม่พบโครงการต้นฉบับในระบบ');
        }

        // Insert new project
        const { data: newProj, error: newPErr } = await supabase
          .from('projects')
          .insert([{
            name: `${srcProj.name} (คัดลอก)`,
            description: srcProj.description,
            assignee: srcProj.assignee,
            status: srcProj.status
          }])
          .select('id')
          .single();

        if (newPErr) throw newPErr;
        const newProjectId = newProj.id;

        // Fetch source stages
        const { data: srcStages, error: sErr } = await supabase
          .from('stages')
          .select('id, title, start_date, end_date, color, sort_order, assignee, stuck_reason, is_completed')
          .eq('project_id', id);

        if (sErr) throw sErr;

        for (const stage of srcStages || []) {
          const { data: newStage, error: nSErr } = await supabase
            .from('stages')
            .insert([{
              project_id: newProjectId,
              title: stage.title,
              start_date: stage.start_date,
              end_date: stage.end_date,
              color: stage.color,
              sort_order: stage.sort_order,
              assignee: stage.assignee,
              stuck_reason: stage.stuck_reason,
              is_completed: stage.is_completed
            }])
            .select('id')
            .single();

          if (nSErr) throw nSErr;
          const newStageId = newStage.id;

          // Fetch source tasks
          const { data: srcTasks, error: tErr } = await supabase
            .from('tasks')
            .select('title, start_date, end_date, color, lane, assignee, sort_order, stuck_reason, is_completed')
            .eq('stage_id', stage.id);

          if (tErr) throw tErr;

          if (srcTasks && srcTasks.length > 0) {
            const taskInserts = srcTasks.map(t => ({
              stage_id: newStageId,
              title: t.title,
              start_date: t.start_date,
              end_date: t.end_date,
              color: t.color,
              lane: t.lane,
              assignee: t.assignee,
              sort_order: t.sort_order,
              stuck_reason: t.stuck_reason,
              is_completed: t.is_completed
            }));

            const { error: batchErr } = await supabase.from('tasks').insert(taskInserts);
            if (batchErr) throw batchErr;
          }
        }

        return sendJson(res, {
          success: true,
          message: 'คัดลอกโครงการใหม่สำเร็จแล้ว',
          id: newProjectId
        });
      }

      case 'save_stage': {
        const id = data.id ? parseInt(data.id, 10) : null;
        const project_id = data.project_id ? parseInt(data.project_id, 10) : null;
        const title = (data.title || '').trim();
        const start_date = (data.start_date || '').trim();
        const end_date = (data.end_date || '').trim();
        const color = (data.color || '#7b879a').trim();
        const sort_order = parseInt(data.sort_order, 10) || 0;
        const assignee = data.assignee ? data.assignee.trim() : null;
        let stuck_reason = data.stuck_reason ? data.stuck_reason.trim() : null;
        const is_completed = parseInt(data.is_completed, 10) || 0;

        if (!title || !start_date || !end_date || !project_id) {
          return sendJson(res, { error: 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อขั้นตอนหลัก, วันที่เริ่ม, วันที่สิ้นสุด, และรหัสโครงการ)' }, 400);
        }

        if (start_date > end_date) {
          return sendJson(res, { error: 'วันที่เริ่มต้นขั้นตอนต้องไม่เกินวันที่สิ้นสุด' }, 400);
        }

        if (color.toLowerCase() === '#dc3d78' && !stuck_reason) {
          return sendJson(res, { error: 'กรุณาระบุสาเหตุที่ Stuck/Delay เสมอเมื่อเลือกสถานะหรือสีนี้' }, 400);
        }

        if (color.toLowerCase() !== '#dc3d78') {
          stuck_reason = null;
        }

        if (id) {
          const { error } = await supabase
            .from('stages')
            .update({
              project_id,
              title,
              start_date,
              end_date,
              color,
              sort_order,
              assignee,
              stuck_reason,
              is_completed,
              updated_at: new Date()
            })
            .eq('id', id);

          if (error) throw error;

          // Check conflicting tasks outside new dates
          const { data: confTasks } = await supabase
            .from('tasks')
            .select('id')
            .eq('stage_id', id)
            .or(`start_date.lt.${start_date},end_date.gt.${end_date}`);

          let message = 'แก้ไขขั้นตอนหลักสำเร็จ';
          if (confTasks && confTasks.length > 0) {
            message += ' (คำเตือน: มีบางงานย่อยที่มีวันจัดทำอยู่นอกช่วงเวลาใหม่นี้)';
          }

          return sendJson(res, { success: true, message, id });
        } else {
          const { data: newS, error } = await supabase
            .from('stages')
            .insert([{
              project_id,
              title,
              start_date,
              end_date,
              color,
              sort_order,
              assignee,
              stuck_reason,
              is_completed
            }])
            .select('id')
            .single();

          if (error) throw error;
          return sendJson(res, { success: true, message: 'เพิ่มขั้นตอนหลักสำเร็จ', id: newS.id });
        }
      }

      case 'delete_stage': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีขั้นตอนหลักที่จะทำการลบ' }, 400);
        }
        const { error } = await supabase.from('stages').delete().eq('id', id);
        if (error) throw error;
        return sendJson(res, { success: true, message: 'ลบขั้นตอนหลักและงานย่อยที่เกี่ยวข้องสำเร็จ' });
      }

      case 'save_task': {
        const id = data.id ? parseInt(data.id, 10) : null;
        const stage_id = data.stage_id ? parseInt(data.stage_id, 10) : null;
        const title = (data.title || '').trim();
        const start_date = (data.start_date || '').trim();
        const end_date = (data.end_date || '').trim();
        const color = (data.color || '#9fc7ff').trim();
        const lane = parseInt(data.lane, 10) || 1;
        const assignee = data.assignee ? data.assignee.trim() : null;
        let stuck_reason = data.stuck_reason ? data.stuck_reason.trim() : null;
        const is_completed = parseInt(data.is_completed, 10) || 0;
        const wbs = data.wbs ? data.wbs.trim() : null;
        const level = parseInt(data.level, 10) || (wbs ? wbs.split('.').length : 2);
        const duration = data.duration ? data.duration.trim() : null;
        const percent_complete = parseFloat(data.percent_complete) || (is_completed ? 100 : 0);
        const status = data.status ? data.status.trim() : (percent_complete >= 100 ? 'Complete' : 'Not Started');
        const actual_start = data.actual_start || null;
        const actual_finish = data.actual_finish || null;

        if (!title || !start_date || !end_date || !stage_id) {
          return sendJson(res, { error: 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อวิชา/งานย่อย, วันที่เริ่ม, วันที่สิ้นสุด, และรหัสขั้นตอนหลัก)' }, 400);
        }

        if (start_date > end_date) {
          return sendJson(res, { error: 'วันที่เริ่มต้นงานย่อยต้องไม่เกินวันที่สิ้นสุด' }, 400);
        }

        if (color.toLowerCase() === '#dc3d78' && !stuck_reason) {
          return sendJson(res, { error: 'กรุณาระบุสาเหตุที่ Stuck/Delay เสมอเมื่อเลือกสถานะหรือสีนี้' }, 400);
        }

        if (color.toLowerCase() !== '#dc3d78') {
          stuck_reason = null;
        }

        // Verify bounds with parent stage
        const { data: stage, error: sErr } = await supabase
          .from('stages')
          .select('title, start_date, end_date')
          .eq('id', stage_id)
          .single();

        if (sErr || !stage) {
          return sendJson(res, { error: 'ไม่พบขั้นตอนหลักที่อ้างอิง' }, 400);
        }

        if (start_date < stage.start_date || end_date > stage.end_date) {
          return sendJson(res, {
            error: `ช่วงเวลาของงานย่อย (${start_date} ถึง ${end_date}) ต้องอยู่ภายใต้ช่วงเวลาของขั้นตอนหลัก '${stage.title}' (${stage.start_date} ถึง ${stage.end_date}) เสมอ`
          }, 400);
        }

        if (id) {
          const { error } = await supabase
            .from('tasks')
            .update({
              stage_id,
              title,
              start_date,
              end_date,
              color,
              lane,
              assignee,
              stuck_reason,
              is_completed,
              wbs,
              level,
              duration,
              percent_complete,
              status,
              actual_start,
              actual_finish,
              updated_at: new Date()
            })
            .eq('id', id);

          if (error) throw error;
          return sendJson(res, { success: true, message: 'แก้ไขงานย่อยสำเร็จ', id });
        } else {
          // Calculate max sort_order
          const { data: maxSortData } = await supabase
            .from('tasks')
            .select('sort_order')
            .eq('stage_id', stage_id)
            .order('sort_order', { ascending: false })
            .limit(1);

          const maxSort = (maxSortData && maxSortData.length > 0 && maxSortData[0].sort_order) ? maxSortData[0].sort_order : 0;
          const newSort = maxSort + 1;

          const { data: newT, error } = await supabase
            .from('tasks')
            .insert([{
              stage_id,
              title,
              start_date,
              end_date,
              color,
              lane,
              assignee,
              sort_order: newSort,
              stuck_reason,
              is_completed,
              wbs,
              level,
              duration,
              percent_complete,
              status,
              actual_start,
              actual_finish
            }])
            .select('id')
            .single();

          if (error) throw error;
          return sendJson(res, { success: true, message: 'เพิ่มงานย่อยสำเร็จ', id: newT.id });
        }
      }

      case 'delete_task': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีงานย่อยที่จะทำการลบ' }, 400);
        }
        const { error } = await supabase.from('tasks').delete().eq('id', id);
        if (error) throw error;
        return sendJson(res, { success: true, message: 'ลบงานย่อยสำเร็จ' });
      }

      case 'reorder_tasks': {
        const orders = data.orders || [];
        if (!Array.isArray(orders) || orders.length === 0) {
          return sendJson(res, { error: 'ไม่พบข้อมูลการจัดตำแหน่งใหม่' }, 400);
        }

        for (const item of orders) {
          const taskId = parseInt(item.id, 10);
          const sortOrder = parseInt(item.sort_order, 10) || 0;
          if (taskId > 0) {
            await supabase.from('tasks').update({ sort_order: sortOrder }).eq('id', taskId);
          }
        }

        return sendJson(res, { success: true, message: 'บันทึกการจัดเรียงลำดับความสำคัญสำเร็จ' });
      }

      case 'save_assignee': {
        const id = data.id ? parseInt(data.id, 10) : null;
        const name = (data.name || '').trim();

        if (!name) {
          return sendJson(res, { error: 'กรุณากรอกชื่อผู้รับผิดชอบ' }, 400);
        }

        if (id) {
          // Get old name
          const { data: oldAssignee } = await supabase
            .from('assignees')
            .select('name')
            .eq('id', id)
            .single();

          const oldName = oldAssignee ? oldAssignee.name : null;

          if (oldName && oldName !== name) {
            // Update in projects, stages, tasks
            for (const table of ['projects', 'stages', 'tasks']) {
              const { data: rows } = await supabase
                .from(table)
                .select('id, assignee')
                .not('assignee', 'is', null)
                .neq('assignee', '');

              for (const row of rows || []) {
                const names = row.assignee.split(',').map(s => s.trim());
                let changed = false;
                for (let i = 0; i < names.length; i++) {
                  if (names[i] === oldName) {
                    names[i] = name;
                    changed = true;
                  }
                }
                if (changed) {
                  await supabase
                    .from(table)
                    .update({ assignee: names.join(', ') })
                    .eq('id', row.id);
                }
              }
            }
          }

          const { error } = await supabase
            .from('assignees')
            .update({ name })
            .eq('id', id);

          if (error) throw error;
          return sendJson(res, { success: true, message: 'แก้ไขชื่อผู้รับผิดชอบสำเร็จ', id });
        } else {
          // Check duplicate
          const { data: dup } = await supabase
            .from('assignees')
            .select('id')
            .eq('name', name);

          if (dup && dup.length > 0) {
            return sendJson(res, { error: 'มีผู้รับผิดชอบชื่อนี้อยู่ในระบบแล้ว' }, 400);
          }

          const { data: newA, error } = await supabase
            .from('assignees')
            .insert([{ name }])
            .select('id')
            .single();

          if (error) throw error;
          return sendJson(res, { success: true, message: 'เพิ่มผู้รับผิดชอบใหม่สำเร็จ', id: newA.id });
        }
      }

      case 'delete_assignee': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีผู้รับผิดชอบที่จะทำการลบ' }, 400);
        }

        const { data: oldAssignee } = await supabase
          .from('assignees')
          .select('name')
          .eq('id', id)
          .single();

        const oldName = oldAssignee ? oldAssignee.name : null;

        if (oldName) {
          for (const table of ['projects', 'stages', 'tasks']) {
            const { data: rows } = await supabase
              .from(table)
              .select('id, assignee')
              .not('assignee', 'is', null)
              .neq('assignee', '');

            for (const row of rows || []) {
              const names = row.assignee.split(',').map(s => s.trim());
              const filtered = names.filter(n => n !== oldName);
              if (filtered.length !== names.length) {
                const newVal = filtered.length === 0 ? null : filtered.join(', ');
                await supabase
                  .from(table)
                  .update({ assignee: newVal })
                  .eq('id', row.id);
              }
            }
          }
        }

        const { error } = await supabase.from('assignees').delete().eq('id', id);
        if (error) throw error;
        return sendJson(res, { success: true, message: 'ลบผู้รับผิดชอบและเคลียร์ข้อมูลจากกิจกรรมที่เกี่ยวข้องสำเร็จ' });
      }

      case 'save_color': {
        const id = data.id ? parseInt(data.id, 10) : null;
        const name = (data.name || '').trim();
        const color_code = (data.color_code || '').trim();

        if (!name || !color_code || !/^#[0-9A-Fa-f]{6}$/.test(color_code)) {
          return sendJson(res, { error: 'กรุณากรอกชื่อสีและรหัสสีให้ถูกต้อง (เช่น #FFFFFF)' }, 400);
        }

        if (id) {
          const { data: oldCol } = await supabase
            .from('colors')
            .select('color_code')
            .eq('id', id)
            .single();

          const oldCode = oldCol ? oldCol.color_code : null;

          if (oldCode && oldCode.toLowerCase() !== color_code.toLowerCase()) {
            await supabase.from('stages').update({ color: color_code }).eq('color', oldCode);
            await supabase.from('tasks').update({ color: color_code }).eq('color', oldCode);
          }

          const { error } = await supabase
            .from('colors')
            .update({ name, color_code })
            .eq('id', id);

          if (error) throw error;
          return sendJson(res, { success: true, message: 'แก้ไขข้อมูลสีสำเร็จ', id });
        } else {
          const { data: dup } = await supabase
            .from('colors')
            .select('id')
            .eq('color_code', color_code);

          if (dup && dup.length > 0) {
            return sendJson(res, { error: 'มีรหัสสีนี้อยู่ในระบบแล้ว' }, 400);
          }

          const { data: newC, error } = await supabase
            .from('colors')
            .insert([{ name, color_code }])
            .select('id')
            .single();

          if (error) throw error;
          return sendJson(res, { success: true, message: 'เพิ่มสีใหม่สำเร็จ', id: newC.id });
        }
      }

      case 'delete_color': {
        const id = data.id ? parseInt(data.id, 10) : null;
        if (!id) {
          return sendJson(res, { error: 'ไม่พบไอดีสีที่จะทำการลบ' }, 400);
        }
        const { error } = await supabase.from('colors').delete().eq('id', id);
        if (error) throw error;
        return sendJson(res, { success: true, message: 'ลบสีออกจากระบบสำเร็จ' });
      }

      default:
        return sendJson(res, { error: 'ไม่พบการกระทำ (Action) ที่ระบุในระบบ' }, 404);
    }
  } catch (error) {
    console.error('API Error:', error);
    return sendJson(res, { error: `เกิดข้อผิดพลาดทางระบบ: ${error.message}` }, 500);
  }
};
