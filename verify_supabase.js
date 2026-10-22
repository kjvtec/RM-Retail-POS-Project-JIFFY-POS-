/**
 * Supabase Connection & Schema Verifier
 * Run: node verify_supabase.js [SUPABASE_URL] [SUPABASE_KEY]
 */

const { createClient } = require('@supabase/supabase-js');
const fs = require('fs');
const path = require('path');

// Try loading from .env.local or .env
for (const file of ['.env.local', '.env']) {
  const p = path.join(__dirname, file);
  if (fs.existsSync(p)) {
    const lines = fs.readFileSync(p, 'utf8').split(/\r?\n/);
    for (const line of lines) {
      const match = line.match(/^\s*([\w.-]+)\s*=\s*(.*)?\s*$/);
      if (match) {
        const key = match[1];
        let val = (match[2] || '').trim().replace(/^['"](.*)['"]$/, '$1');
        if (!process.env[key]) process.env[key] = val;
      }
    }
  }
}

const url = process.argv[2] || process.env.SUPABASE_URL;
const key = process.argv[3] || process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;

if (!url || !key) {
  console.log('===========================================================');
  console.log('⚠️  กรุณาระบุ SUPABASE_URL และ SUPABASE_KEY');
  console.log('ตัวอย่าง: node verify_supabase.js https://xyz.supabase.co eyJhbGci...');
  console.log('หรือบันทึกไว้ในไฟล์ .env.local');
  console.log('===========================================================');
  process.exit(1);
}

async function verify() {
  console.log(`📡 กำลังตรวจสอบการเชื่อมต่อกับ Supabase: ${url}`);
  const client = createClient(url, key, { auth: { persistSession: false } });

  const tables = ['projects', 'stages', 'tasks', 'assignees', 'colors', 'users'];
  let allGood = true;

  for (const table of tables) {
    try {
      const { data, error, count } = await client.from(table).select('*', { count: 'exact', head: true });
      if (error) {
        console.log(`❌ ตาราง public.${table}: ไม่พบ หรือเกิดข้อผิดพลาด (${error.message})`);
        allGood = false;
      } else {
        console.log(`✅ ตาราง public.${table}: เชื่อมต่อสำเร็จ (มีข้อมูล ${count !== null ? count : 0} แถว)`);
      }
    } catch (err) {
      console.log(`❌ ตาราง public.${table}: ${err.message}`);
      allGood = false;
    }
  }

  if (allGood) {
    console.log('\n🎉 ยอดเยี่ยม! ฐานข้อมูล Supabase พร้อมใช้งานกับระบบ 100%');
  } else {
    console.log('\n⚠️ ตรวจพบตารางบางส่วนยังไม่ถูกสร้าง กรุณานำไฟล์ supabase_schema.sql ไปรันใน Supabase SQL Editor');
  }
}

verify();
