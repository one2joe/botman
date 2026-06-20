# น้องเวบ Personality — LINE Bot Design

## Overview
เปลี่ยนบุคลิก LINE Bot จากสุภาพ (ครับ) เป็น casual หญิง "น้องเวบ" (ค่าาา, 💕, น่ารัก)
พร้อมเพิ่ม triggers, media handlers, และปรับ Onboarding Conversation.

## Files ที่ต้องแก้ไข

| File | การเปลี่ยนแปลง |
|---|---|
| `public/index.php` | แก้ response strings, เพิ่ม triggers, เพิ่ม `[Sticker]`/`[Location]` handlers |
| `src/Conversations/OnboardingConversation.php` | ปรับเป็น 5 ขั้นตอน, แก้โทนข้อความ |
| `src/Conversations/BaseConversation.php` | ขยาย cancel triggers เป็น 4 คำ, ปรับ cancel message |
| `tests/Feature/BotResponseTest.php` | เพิ่ม/อัปเดต tests ทุกเคส |
| `tests/Unit/LoggerTest.php` | ไม่ต้องแก้ |

## Intents

### 1. ทักทาย (Greeting)
- **Triggers**: `สวัสดี`, `ไง`, `hello`, `หวัดดี`, `เฮ้ย`
- **Response**: "สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?"
- **Buttons**: แนะนำตัว, ดูความสามารถ, ช่วยเหลือ

### 2. แนะนำตัว / Onboarding
- **Triggers**: `แนะนำตัว`, `รู้จัก`, `เป็นใคร`
- **Response**: เริ่ม OnboardingConversation

### 3. ดูความสามารถ
- **Triggers**: `ดูความสามารถ`, `ทำอะไรได้บ้าง`, `สอนการใช้งาน`, `help`
- **Response**: "โอ้ยยย น้องดีใจที่คุณอยากรู้ความสามารถของน้องเลยค่าา~ 💖 ..."

### 4. ช่วยเหลือ (Help Menu)
- **Triggers**: `ช่วยเหลือ`, `เมนู`, `menu`
- **Response**: "นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕 ..."

### 5. รูปภาพ
- **Trigger**: `[Image]` (LINE auto)
- **Response**: "ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ อยากให้เวบช่วยอะไรกับรูปนี้ดีคะ?"

### 6. สติกเกอร์
- **Trigger**: `[Sticker]` (LINE auto)
- **Response**: "น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕"

### 7. ตำแหน่งที่ตั้ง
- **Trigger**: `[Location]` (LINE auto)
- **Response**: "ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ ขาาา อยากให้ช่วยหาข้อมูลอะไรตรงนี้ไหมคะ?"

### 8. ยกเลิก (Cancel)
- **Triggers**: `ยกเลิก`, `cancel`, `พอ`, `หยุด`
- **ใน Conversation**: "ยกเลิกแล้วนะคะ~ ✅ ไม่เป็นไรเลยค่าาา อยากทำอะไรต่อบอกน้องได้เลย"
- **นอก Conversation**: "อืมม~ พิมพ์ ช่วยเหลือ เพื่อดูเมนูนะค้าา"

### 9. Fallback
- **Response**: "อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅 ลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา"

## Conversation Flow (Onboarding) — 5 Steps

1. ทักทายน่ารัก
2. ถามชื่อผู้ใช้
3. ทักกลับด้วยชื่อ
4. โชว์ความสามารถสั้น ๆ
5. จบ "พร้อมช่วยแล้วนะค้าา~"

Cancel ทุกขั้นตอน → ยกเลิก + จบ conversation

## Technical Notes

- **Multiple triggers**: แต่ละ trigger value ใช้แยก `hears()` call (ไม่ใช้ regex) ตาม pattern เดิมของ codebase
- **Cancel check**: `BaseConversation::ask()` ตรวจ `in_array(trim($answer->getText()), ['ยกเลิก', 'cancel', 'พอ', 'หยุด'])` แทนการเทียบแค่ `ยกเลิก`
- **Media handlers**: LINE driver ส่ง `[Sticker]` และ `[Location]` เป็นข้อความ plain text — ใช้ `hears('\[Sticker\]')` และ `hears('\[Location\]')` เช่นเดียวกับ `[Image]`

## Testing Strategy

- ทุก intent มี test: ส่ง trigger → ตรวจ response string
- Cross-request conversation test (shared cache)
- Cancel in/out of conversation
- Media handlers ทั้ง 3 แบบ
- New triggers แต่ละอัน

## Implementation Order

1. อัปเดต `OnboardingConversation.php` (5 steps, new tone)
2. อัปเดต `BaseConversation.php` (cancel message)
3. อัปเดต `public/index.php` (all handlers + triggers)
4. อัปเดต tests (ทุกเคส + เพิ่มเคสใหม่)
5. รัน tests → แก้จนผ่าน
