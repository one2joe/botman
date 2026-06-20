# Intent-Response Table

## Greeting (ทักทาย)
- **Trigger**: `สวัสดี`, `ไง`, `hello`, `หวัดดี`, `เฮ้ย`
- **Response**: "สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?"
- **Buttons**: [แนะนำตัว] [ดูความสามารถ] [ช่วยเหลือ]
- **Type**: Stateless

## Onboarding (แนะนำตัว)
- **Trigger**: `แนะนำตัว`, `รู้จัก`, `เป็นใคร`
- **Response**: Start `OnboardingConversation`
- **Steps**:
  1. Say: "สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕"
  2. Ask: "ขอชื่อเล่นหน่อยได้ไหมคะ?" — [ยกเลิก] **(required)**
  3. Say: "ยินดีที่ได้รู้จัก {name} นะค้าา~ 💕"
  4. Ask: "สนใจอยากให้บอทช่วยอะไรเป็นหลัก?" — [คุยเล่น] [ลองส่งรูป] [ทดสอบ webhook] [ข้าม] [ยกเลิก]
  5. Say: Summary + "พร้อมช่วยแล้วนะค้าา~"
- **Type**: Conversational
- **Cancel**: Quick reply (value=`cancel`) → จบ conversation
- **Skip**: Quick reply (value=`skip`) → ข้ามคำถาม ไปขั้นตอนถัดไป

## Help (ช่วยเหลือ)
- **Trigger**: `ช่วยเหลือ`, `เมนู`, `menu`, `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `help`
- **Response**: "นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕\n• ส่งรูปภาพ / สติกเกอร์ / ตำแหน่ง\n• พิมพ์ ช่วยเหลือ\n• พิมพ์ ยกเลิก\nลองทำอะไรก็ได้เลยค่าาา~"
- **Type**: Stateless

## Tour (สอนการใช้งาน)
- **Trigger**: `สอนการใช้งาน`
- **Response**: Start `TourConversation`
- **Steps**:
  1. Say: "มาแล้วว~ เดี๋ยวน้องเวบพาทัวร์เอง! 💕"
  2. Ask: "ลองส่งรูปภาพมาให้ดูหน่อยสิ~" — [ข้าม] [ยกเลิก]
     - ส่ง `[Image]` → "ว้าววว~ รูปสวยมากเลยค่าาา 💕"
     - ผิด → "ไม่ใช่รูปนะค้าาา~ ลองส่งรูปภาพมาดูหน่อยสิคะ" — loop
     - ข้าม → ไป Step 3
  3. Ask: "ลองส่งสติกเกอร์น่ารักๆ ดูมั้ย~" — [ข้าม] [ยกเลิก]
     - ส่ง `[Sticker]` → "น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕"
     - ผิด → loop
     - ข้าม → ไป Step 4
  4. Ask: "ลองส่งตำแหน่งที่ตั้งดูหน่อยสิ~" — [ข้าม] [ยกเลิก]
     - ส่ง `[Location]` → "ได้รับตำแหน่งแล้วค่าาา 📍"
     - ผิด → loop
     - ข้าม → ไป Step 5
  5. Say: "จบแค่นี้ก่อนนะค้าา~ 💕 พิมพ์ ช่วยเหลือ ได้ตลอดเลย"
- **Type**: Conversational, Interactive
- **Cancel**: Quick reply (value=`cancel`) → จบ tour ทันที
- **Skip**: Quick reply (value=`skip`) → ข้ามขั้นตอนนี้ ไปถัดไป
- **Auto-next**: เมื่อส่ง media ถูก → ไปขั้นตอนถัดไปทันที (ไม่ต้องกด continue)

## Image Handler
- **Trigger**: `[Image]` (LINE driver auto)
- **Response**: "ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ อยากให้เวบช่วยอะไรกับรูปนี้ดีคะ?"
- **Type**: Stateless

## Sticker Handler
- **Trigger**: `[Sticker]` (LINE driver auto)
- **Response**: "น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕"
- **Type**: Stateless

## Location Handler
- **Trigger**: `[Location]` (LINE driver auto)
- **Response**: "ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ ขาาา อยากให้ช่วยหาข้อมูลอะไรตรงนี้ไหมคะ?"
- **Type**: Stateless

## Fallback (ไม่เข้าใจ)
- **Trigger**: คำที่ไม่ตรง intent ใด
- **Response**: "อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅\nลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา จะได้แสดงสิ่งที่น้องทำได้ทั้งหมดเลย"
- **Type**: Stateless (ไม่นับจำนวนครั้ง)

---

## Conversation Flow

### Happy Path
1. ผู้ใช้ทักทาย → Greeting + แสดงปุ่ม
2. ผู้ใช้เลือก "แนะนำตัว" → Onboarding (ถามชื่อ → ถามเป้าหมาย → สรุป)
3. ผู้ใช้ส่ง Media (รูป, สติกเกอร์, ตำแหน่ง) → Media handler
4. ผู้ใช้พิมพ์ "สอนการใช้งาน" → Tour interactive
5. ผู้ใช้พิมพ์ "ยกเลิก" (กรณีมี conversation) → Cancel conversation

### Error Path
1. พิมพ์ไม่ตรง intent → Fallback
2. ส่ง media ผิดใน Tour → Loop + prompt ซ้ำ
