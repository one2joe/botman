# น้องเวบ Personality — LINE Bot Design

## Overview
เปลี่ยนบุคลิก LINE Bot จากสุภาพ (ครับ) เป็น casual หญิง "น้องเวบ" (ค่าาา, 💕, น่ารัก)
พร้อมเพิ่ม triggers, media handlers, และปรับ Onboarding Conversation.

## Files ที่ต้องแก้ไข

| File | การเปลี่ยนแปลง |
|---|---|
| `public/index.php` | แก้ response strings, เพิ่ม triggers, แยก Capability/Help, เพิ่ม `[Sticker]`/`[Location]` handlers |
| `src/Conversations/OnboardingConversation.php` | ปรับเป็น 5 steps (รวม askGoal), แก้โทนข้อความ |
| `src/Conversations/BaseConversation.php` | เปลี่ยน cancel check เป็น `isInteractiveMessageReply()`, ปรับ cancel message |
| `tests/Feature/BotResponseTest.php` | เพิ่ม/อัปเดต tests ทุกเคส |

## Intents

### 1. ทักทาย (Greeting)
- **Triggers**: `สวัสดี`, `ไง`, `hello`, `หวัดดี`, `เฮ้ย`
- **Response**: "สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?"
- **Buttons**: แนะนำตัว, ดูความสามารถ, ช่วยเหลือ

### 2. แนะนำตัว / Onboarding
- **Triggers**: `แนะนำตัว`, `รู้จัก`, `เป็นใคร`
- **Steps**:
  1. Say: "สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕"
  2. Ask: "ขอชื่อเล่นหน่อยได้ไหมคะ? เรียกอะไรดีเอ่ย~"
  3. Say: "ยินดีที่ได้รู้จัก {name} นะค้าา~ 💕"
  4. Ask: "สนใจอยากให้บอทช่วยอะไรเป็นหลัก?" — [คุยเล่น] [ลองส่งรูป] [ทดสอบ webhook] [ข้าม] [ยกเลิก]
     - ข้าม (in-closure) → ไปสรุป
  5. Say: "{name} เลือก {goal} ไว้ใช่มั้ยเอ่ย~ พร้อมช่วยแล้วนะค้าา..."
- **Cancel**: Quick reply value=`cancel`, ตรวจ `isInteractiveMessageReply()` ใน `BaseConversation::ask()`
- **Skip**: ตรวจ `getText() === 'ข้าม'` ใน closure ของ askGoal

### 3. ดูความสามารถ (Capability)
- **Triggers**: `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `สอนการใช้งาน`, `help`
- **Response**: "โอ้ยยย น้องดีใจที่คุณอยากรู้ความสามารถของน้องเลยค่าา~ 💖 ..."

### 4. ช่วยเหลือ (Help Menu)
- **Triggers**: `ช่วยเหลือ`, `เมนู`, `menu`
- **Response**: "นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕 ..."

### 5. รูปภาพ
- **Trigger**: `[Image]` (LINE auto)
- **Response**: "ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ..."

### 6. สติกเกอร์
- **Trigger**: `[Sticker]` (LINE auto)
- **Response**: "น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕"

### 7. ตำแหน่งที่ตั้ง
- **Trigger**: `[Location]` (LINE auto)
- **Response**: "ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ..."

### 8. ยกเลิก (Cancel)
- **ใน Conversation**: "ยกเลิกแล้วนะคะ~ ✅ ไม่เป็นไรเลยค่าาา อยากทำอะไรต่อบอกน้องได้เลย"
- **นอก Conversation**: Fallback (ไม่แยก handler)
- **Mechanism**: Quick reply button (value=`cancel`) เท่านั้น, ตรวจ `isInteractiveMessageReply()`

### 9. Fallback
- **Response**: "อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅 ลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา"

## Conversation Flow (Onboarding) — 5 Steps

1. Say ต้อนรับ: "สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕"
2. Ask ชื่อ (required): "ขอชื่อเล่นหน่อยได้ไหมคะ?"
3. Say: "ยินดีที่ได้รู้จัก {name} นะค้าา~ 💕"
4. Ask เป้าหมาย (optional, ข้ามได้):
   - Buttons: คุยเล่น, ลองส่งรูป, ทดสอบ webhook, ข้าม, ยกเลิก
   - ข้าม → ไปสรุป
5. Say สรุป: "{name} เลือก {goal} ไว้ใช่มั้ย..."

Cancel ทุกขั้นตอน → ยกเลิก + จบ conversation

## Technical Notes

- **Multiple triggers**: แต่ละ trigger value ใช้แยก `hears()` call (ไม่ใช้ regex) ตาม pattern เดิม
- **Cancel check**: `BaseConversation::ask()` wrapper ตรวจ `$answer->isInteractiveMessageReply() && $answer->getValue() === 'cancel'`
- **Skip check**: ตรวจ `trim($answer->getText()) === 'ข้าม'` ภายใน closure ของ askGoal (ไม่ใช้ `skipsConversation`)
- **Media handlers**: LINE driver ส่ง `[Sticker]` และ `[Location]` เป็นข้อความ plain text

## Implementation Order

1. BaseConversation — cancel mechanism + message
2. OnboardingConversation — 5 steps + nongweb tone
3. index.php — all handlers + triggers + media handlers
4. BotResponseTest — ทุกเคส
5. รัน tests → แก้จนผ่าน
