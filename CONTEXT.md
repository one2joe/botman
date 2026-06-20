# Context: LINE Bot "น้องเวบ"

## Glossary

### น้องเวบ
ชื่อทางการของ LINE bot มีบุคลิก casual หญิง ใช้คำลงท้ายแบบ "ค่าาา" "คะ" และ emoji (💕 💖 😘 😂) ตอบสนองร่าเริง เป็นกันเอง

### Intent
หัวข้อที่บอทเข้าใจ แต่ละ Intent มีพฤติกรรมของตัวเอง — บางอันเป็น stateless (ตอบครั้งเดียวจบ) บางอันเป็น conversational (มีหลายขั้นตอน)

### Greeting
Intent ต้อนรับ Stateless ผู้ใช้พิมพ์คำทักทาย → บอทตอบกลับพร้อม quick reply buttons (แนะนำตัว, ดูความสามารถ, ช่วยเหลือ)

### Help
Intent แสดงรายการความสามารถของบอท Stateless รวม trigger words: `ช่วยเหลือ`, `เมนู`, `menu`, `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `help`

### Onboarding
Intent แนะนำตัวและถามข้อมูลผู้ใช้ Conversational 5 steps: ต้อนรับ → ถามชื่อ (required) → ทักด้วยชื่อ → ถามเป้าหมาย (optional, ข้ามได้) → สรุป

### Tour
Intent สอนการใช้งานแบบ interactive Conversational ให้ผู้ใช้ลองส่ง media จริง (Image, Sticker, Location) แต่ละ step มีปุ่มข้ามและยกเลิก

### Cancel
การหยุด conversation ทันที ใช้ quick reply button เท่านั้น (value = `cancel`) ไม่รองรับ text matching เพื่อป้องกัน false positive

### Skip
การข้ามคำถามใน conversation ไปขั้นตอนถัดไป ใช้ quick reply button เฉพาะขั้นตอนที่ optional เท่านั้น

### Fallback
คำตอบเมื่อบอทไม่เข้าใจ input Stateless ไม่มีการนับจำนวนครั้ง

### Media Handler
Intent ที่รองรับการรับ media จาก LINE platform LINE driver แปลง media เป็นข้อความอัตโนมัติ: `[Image]`, `[Sticker]`, `[Location]`
