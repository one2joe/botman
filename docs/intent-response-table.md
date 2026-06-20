# Intent-Response Table

## Greeting (ทักทาย)
- **Triggers**: `สวัสดี`, `ไง`, `hello`, `หวัดดี`, `เฮ้ย`
- **Response**: "สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?"
- **Buttons**: [แนะนำตัว] [ดูความสามารถ] [ช่วยเหลือ]
- **Type**: Stateless

## Onboarding (แนะนำตัว)
- **Triggers**: `แนะนำตัว`, `รู้จัก`, `เป็นใคร`
- **Response**: Start `OnboardingConversation`
- **Steps**:
  1. Say: "สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕"
  2. Ask: "ขอชื่อเล่นหน่อยได้ไหมคะ? เรียกอะไรดีเอ่ย~" — [ยกเลิก] **(required)**
  3. Say: "ยินดีที่ได้รู้จัก {name} นะค้าา~ 💕"
  4. Ask: "สนใจอยากให้บอทช่วยอะไรเป็นหลัก?" — [คุยเล่น] [ลองส่งรูป] [ทดสอบ webhook] [ข้าม] [ยกเลิก] **(optional)**
     - ข้าม → ไปสรุป
  5. Say: "{name} เลือก {goal} ไว้ใช่มั้ยเอ่ย~ พร้อมช่วยแล้วนะค้าาา ถ้าอยากรู้อะไรเพิ่มเติมพิมพ์ ช่วยเหลือ ได้เลยนะคะ 😘"
- **Type**: Conversational
- **Cancel**: Quick reply (value=`cancel`) → จบ conversation
- **Skip**: ตรวจ `getText() === 'ข้าม'` ใน closure ของ askGoal → ข้ามไปสรุป

## Capability (ดูความสามารถ)
- **Triggers**: `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `สอนการใช้งาน`, `help`
- **Response**: "โอ้ยยย น้องดีใจที่คุณอยากรู้ความสามารถของน้องเลยค่าา~ 💖\nลองทำตามนี้ได้เลยนะคะ:\n• ส่งรูปภาพมา\n• ส่งตำแหน่งที่ตั้ง\n• ส่งสติกเกอร์\n• พิมพ์ ยกเลิก (เมื่อไหร่ก็ได้)\nหรือจะคุยเล่นก็ได้เลยค่าาา 😘"
- **Type**: Stateless

## Help (ช่วยเหลือ)
- **Triggers**: `ช่วยเหลือ`, `เมนู`, `menu`
- **Response**: "นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕\n• ส่งรูปภาพ / สติกเกอร์ / ตำแหน่ง\n• พิมพ์ ช่วยเหลือ\n• พิมพ์ ยกเลิก\nลองทำอะไรก็ได้เลยค่าาา~"
- **Type**: Stateless

## Tour (สอนการใช้งาน) — DEFERRED
- **Trigger**: — (ยังไม่ implement)
- **Type**: Conversational, Interactive

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
4. ผู้ใช้พิมพ์ "ดูความสามารถ" → Capability (stateless)
5. ผู้ใช้พิมพ์ "ช่วยเหลือ" → Help menu (stateless)
6. ผู้ใช้พิมพ์ "ยกเลิก" (กรณีมี conversation) → Cancel conversation

### Error Path
1. พิมพ์ไม่ตรง intent → Fallback
2. กดยกเลิกใน conversation → Cancel, จบ conversation ทันที
