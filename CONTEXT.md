# Context: LINE Bot "น้องเวบ"

## Glossary

### น้องเวบ
ชื่อทางการของ LINE bot มีบุคลิก casual หญิง ใช้คำลงท้ายแบบ "ค่าาา" "คะ" และ emoji (💕 💖 😘 😂) ตอบสนองร่าเริง เป็นกันเอง

### Intent
หัวข้อที่บอทเข้าใจ แต่ละ Intent มีพฤติกรรมของตัวเอง — บางอันเป็น stateless (ตอบครั้งเดียวจบ) บางอันเป็น conversational (มีหลายขั้นตอน)

### Greeting
Intent ต้อนรับ Stateless Triggers: `สวัสดี`, `ไง`, `hello`, `หวัดดี`, `เฮ้ย` ตอบกลับพร้อม quick reply buttons (แนะนำตัว, ดูความสามารถ, ช่วยเหลือ)

### Onboarding
Intent แนะนำตัวและถามข้อมูลผู้ใช้ Conversational 5 steps:
1. ต้อนรับ: "สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕"
2. ถามชื่อ (required) พร้อมปุ่มยกเลิก
3. ทักกลับด้วยชื่อ
4. ถามเป้าหมาย (optional, ข้ามได้) พร้อมปุ่ม: คุยเล่น, ลองส่งรูป, ทดสอบ webhook, ข้าม, ยกเลิก
5. สรุป + พร้อมช่วย

### Capability
Intent แสดงความสามารถของบอทแบบละเอียด Stateless Triggers: `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `สอนการใช้งาน`, `help`

### Help
Intent แสดงรายการคำสั่งแบบสั้น Stateless Triggers: `ช่วยเหลือ`, `เมนู`, `menu`

### Tour
Intent สอนการใช้งานแบบ interactive Conversational (deferred — ยังไม่ implement)

### Cancel
การหยุด conversation ทันที ใช้ quick reply button เท่านั้น (value = `cancel`) ตรวจด้วย `isInteractiveMessageReply() && getValue() === 'cancel'` ใน `BaseConversation::ask()` wrapper ไม่รองรับ text matching

### Skip
การข้ามคำถาม optional ไปขั้นตอนถัดไป ตรวจ `getText() === 'ข้าม'` ภายใน closure (ไม่ใช้ `skipsConversation`)

### Fallback
คำตอบเมื่อบอทไม่เข้าใจ input Stateless ไม่มีการนับจำนวนครั้ง

### Media Handler
Intent ที่รองรับการรับ media จาก LINE platform LINE driver แปลง media เป็นข้อความอัตโนมัติ: `[Image]`, `[Sticker]`, `[Location]`
