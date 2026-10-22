# RM-Retail POS Project (JIFFY POS) & Gantt Timeline (Vercel & Supabase Edition)

ระบบบริหารจัดการโครงการและแผนภูมิความคืบหน้า (Project Timeline Gantt Chart) สถาปัตยกรรมคลาวด์เซิร์ฟเวอร์เลส (Serverless) ทำงานบน **Vercel** ร่วมกับฐานข้อมูล **Supabase (PostgreSQL)**

---

## ✨ คุณสมบัติเด่นของระบบ (Key Features)

หน้าแรก `/` และ `/project_timeline.html?project_id=1` แสดง Progress ของโครงการหลักโดยคำนวณตามโครงสร้างสูตรใน `Project Detail Work Plan RM-Retail POS V2.0_20261015.xlsx`: Baseline Planned จากคอลัมน์ E และ Actual จาก `% Work Complete` คอลัมน์ F โดยแสดงจำนวนเต็มแบบปัดลง ส่วน Behind Plan เป็นผลต่างของจำนวนเต็มที่แสดงและแสดงหน่วยเป็น `%` วันที่เลือกบนหน้าจอเป็นวันที่อ้างอิงของ Baseline และไฟล์ Export

สูตร E4 ในไฟล์ต้นทางมีช่องว่างใน `AVERAGE(E5,,E30,E35,E93)` ซึ่งทำให้ Baseline ต่ำกว่าที่ควร ระบบใช้ `AVERAGE(E5,E30,E35,E93)` และแก้สูตรนี้ในไฟล์ Export ด้วย ณ 22 ต.ค. 2569 หน้าจอแสดง Baseline 23%, Actual 16%, Behind Plan 7%

ปุ่ม **Export Excel** ดึงข้อมูลปัจจุบันจากฐานข้อมูลและเติมลงในไฟล์ต้นแบบเดิม โดยคงสองชีต รูปแบบ และสูตรของ Excel พร้อมวันที่อ้างอิงใน O1 งานที่เพิ่มใหม่และยังไม่มีแถวที่จับคู่ในต้นแบบจะได้รับข้อความผิดพลาดชัดเจนเพื่อป้องกันการส่งออกข้อมูลที่ตกหล่น

- **Dynamic Gantt Timeline Chart**: แผนภูมิ Gantt คำนวณช่วงเวลารายสัปดาห์และขอบเขตข้ามปีอัตโนมัติตามระยะเวลางานจริงของแต่ละโครงการ
- **Multi-Project Management**: รองรับการสร้าง, แก้ไข, ทำซ้ำโครงการ (Duplicate Project แบบ Deep Copy ทั้ง Stages และ Tasks)
- **Executive Dashboard**: แดชบอร์ดวิเคราะห์โครงการพร้อมเกจครึ่งวงกลม (Speedometer Gauge) แสดงเปอร์เซ็นต์ความคืบหน้าเฉลี่ย และสถิติภาพรวม
- **Drag & Drop Task Reordering**: จัดเรียงลำดับความสำคัญของงานย่อยด้วยการลากวางได้อย่างง่ายดาย
- **Stuck & Delay Tracking**: บันทึกสาเหตุและตรวจสอบจุดติดขัด (Stuck Reasons) เมื่องานล่าช้าหรือตกอยู่ในสถานะ At Risk
- **Custom Color & Assignee Presets**: จัดการชุดสีประจำกลุ่มงานและรายชื่อผู้รับผิดชอบพร้อมระบบ Cascade อัปเดตข้อมูลอัตโนมัติ
- **Modern Security**: ระบบเข้าสู่ระบบแอดมินด้วย HTTP-only Cookie และ JWT ปลอดภัยสูงสุดบน Vercel Serverless

---

## 🚀 เริ่มต้นใช้งานด่วน (Quick Start)

### 1. นำเข้าฐานข้อมูลใน Supabase
- เปิด [Supabase SQL Editor](https://supabase.com)
- นำเข้าและรันคำสั่งจากไฟล์ [`supabase_schema.sql`](supabase_schema.sql)

### 2. รันระบบในเครื่อง (Local Development)
```bash
# ติดตั้งไลบรารี
npm install

# คัดลอกและตั้งค่า Environment Variables
cp .env.example .env.local

# เริ่มรันเซิร์ฟเวอร์
npm start
```
เปิดบราวเซอร์ที่: [http://localhost:3000](http://localhost:3000) เพื่อดูหน้า Progress ของโครงการหลัก (`project_id=1`) ส่วน Dashboard เดิมอยู่ที่ `/dashboard.html`

### 3. Deploy ขึ้น Vercel
- อ่านคู่มือการติดตั้งฉบับเต็มได้ที่ [`VERCEL_SUPABASE_DEPLOY_GUIDE.md`](VERCEL_SUPABASE_DEPLOY_GUIDE.md)

---

## 🔐 บัญชีผู้ดูแลระบบเริ่มต้น (Default Admin)

- **Username**: `admin`
- **Password**: `admin123`
