# 📋 دليل Frontend: واجهات إدارة الأدمنز والإشعارات

## 📚 جدول المحتويات
- [نظرة عامة](#نظرة-عامة)
- [API Endpoints](#api-endpoints)
- [واجهة إدارة الأدمنز](#واجهة-إدارة-الأدمنز)
- [واجهة إعدادات الإشعارات](#واجهة-إعدادات-الإشعارات)
- [TypeScript Interfaces](#typescript-interfaces)
- [أمثلة على الكود](#أمثلة-على-الكود)
- [Validation Rules](#validation-rules)

---

## 🎯 نظرة عامة

### الهدف:
تطوير واجهتين رئيسيتين:
1. **صفحة إدارة الأدمنز** - إضافة/تعديل/حذف المسؤولين عن استلام الإشعارات
2. **تبويب إعدادات الإشعارات** - إعدادات الإشعارات الافتراضية والتحكم بالقنوات

### آلية العمل:
- النظام يجمع المستلمين من **مصدرين**:
  1. جدول `backup_admins` - الأدمنز المضافين يدوياً
  2. حقول `backup_settings` - القيم الافتراضية (default)
- كل حقل يدعم **قيم متعددة مفصولة بفاصلة** (comma-separated)
- مثال: `"admin1@test.com,admin2@test.com,admin3@test.com"`
- النظام يزيل التكرار تلقائياً عند الإرسال

---

## 🗺️ API Endpoints

### 1. Admins Management

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| GET | `/api/v1/backup/admins` | - | جلب قائمة جميع الأدمنز |
| POST | `/api/v1/backup/admins` | انظر للأسفل | إضافة أدمن جديد |
| PUT | `/api/v1/backup/admins/{id}` | انظر للأسفل | تعديل بيانات أدمن |
| DELETE | `/api/v1/backup/admins/{id}` | - | حذف أدمن |

**مثال Request لـ `POST /api/v1/backup/admins`:**
```json
{
  "name": "Ahmad Ali",
  "email": "ahmad@example.com,ahmad2@example.com",
  "telegram_id": "123456789,987654321",
  "webhook_url": "https://webhook.site/test1,https://webhook.site/test2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true
}
```

**مثال Response:**
```json
{
  "id": 1,
  "name": "Ahmad Ali",
  "email": "ahmad@example.com,ahmad2@example.com",
  "telegram_id": "123456789,987654321",
  "webhook_url": "https://webhook.site/test1,https://webhook.site/test2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true,
  "created_at": "2025-11-05T10:30:00.000000Z",
  "updated_at": "2025-11-05T10:30:00.000000Z"
}
```

**مثال Response لـ `GET /api/v1/backup/admins`:**
```json
[
  {
    "id": 1,
    "name": "Ahmad Ali",
    "email": "ahmad@example.com,ahmad2@example.com",
    "telegram_id": "123456789",
    "webhook_url": "https://webhook.site/test",
    "notify_via": ["email", "telegram"],
    "active": true,
    "created_at": "2025-11-05T10:30:00.000000Z",
    "updated_at": "2025-11-05T10:30:00.000000Z"
  },
  {
    "id": 2,
    "name": "Sara Ahmed",
    "email": "sara@example.com",
    "telegram_id": null,
    "webhook_url": null,
    "notify_via": ["email"],
    "active": true,
    "created_at": "2025-11-05T10:35:00.000000Z",
    "updated_at": "2025-11-05T10:35:00.000000Z"
  }
]
```

### 2. Settings (Notifications Tab)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| GET | `/api/v1/backup/settings` | - | جلب جميع الإعدادات |
| PUT | `/api/v1/backup/settings` | انظر للأسفل | تحديث الإعدادات |

**الحقول المتعلقة بالإشعارات في Settings:**

```json
{
  "notify_enabled": true,
  "notify_on": "both",

  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,

  "emails": "default1@example.com,default2@example.com",
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "111111111,222222222",
  "webhook_urls": "https://webhook.site/default1,https://webhook.site/default2",
  "webhook_secret": "my-secret-key"
}
```

---

## 📱 واجهة إدارة الأدمنز

### المكان:
صفحة منفصلة: `/admins` أو `/backup/admins`

### المكونات المطلوبة:

#### 1. **Admin Table Component**

**الأعمدة (Columns):**
- **Name** (string)
- **Email** (string - قد تكون متعددة مفصولة بفاصلة)
- **Telegram ID** (string - قد تكون متعددة)
- **Webhook URL** (string - قد تكون متعددة)
- **Notify Via** (badges - array)
- **Active** (toggle switch)
- **Actions** (Edit, Delete)

**مثال على عرض البيانات:**

| Name | Email | Telegram ID | Webhook | Notify Via | Active | Actions |
|------|-------|-------------|---------|------------|--------|---------|
| Ahmad Ali | ahmad@test.com, ahmad2@test.com | 123456789, 987654321 | webhook.site/test1, ... | 📧 📱 🔗 | ✅ | ✏️ 🗑️ |
| Sara Ahmed | sara@test.com | - | - | 📧 | ✅ | ✏️ 🗑️ |

**Features:**
- **Search** - البحث بالاسم أو Email
- **Filter by Notify Via** - فلترة حسب القناة (Email, Telegram, Webhook)
- **Filter by Active** - فلترة حسب الحالة (فعّال/معطّل)
- **Sort** - ترتيب حسب (Name, Created Date)
- **Pagination** - إن وجدت أعداد كبيرة

#### 2. **Add/Edit Admin Dialog/Modal**

**الحقول (Fields):**

```vue
<template>
  <Dialog v-model="showDialog">
    <DialogContent>
      <DialogTitle>{{ isEdit ? 'تعديل أدمن' : 'إضافة أدمن جديد' }}</DialogTitle>

      <!-- Name Field -->
      <FormField>
        <Label>الاسم *</Label>
        <Input v-model="form.name" placeholder="أحمد علي" required />
        <ErrorMessage v-if="errors.name">{{ errors.name }}</ErrorMessage>
      </FormField>

      <!-- Email Field (يدعم عدة emails مفصولة بفاصلة) -->
      <FormField>
        <Label>البريد الإلكتروني</Label>
        <Input
          v-model="form.email"
          placeholder="admin1@example.com, admin2@example.com"
          type="text"
        />
        <HelpText>يمكنك إدخال عدة emails مفصولة بفاصلة (,)</HelpText>
        <ErrorMessage v-if="errors.email">{{ errors.email }}</ErrorMessage>
      </FormField>

      <!-- Telegram ID Field (يدعم عدة IDs مفصولة بفاصلة) -->
      <FormField>
        <Label>Telegram Chat ID</Label>
        <Input
          v-model="form.telegram_id"
          placeholder="123456789, 987654321"
          type="text"
        />
        <HelpText>يمكنك إدخال عدة Telegram IDs مفصولة بفاصلة (,)</HelpText>
        <ErrorMessage v-if="errors.telegram_id">{{ errors.telegram_id }}</ErrorMessage>
      </FormField>

      <!-- Webhook URL Field (يدعم عدة URLs مفصولة بفاصلة) -->
      <FormField>
        <Label>Webhook URL</Label>
        <Input
          v-model="form.webhook_url"
          placeholder="https://webhook.site/test1, https://webhook.site/test2"
          type="text"
        />
        <HelpText>يمكنك إدخال عدة Webhook URLs مفصولة بفاصلة (,)</HelpText>
        <ErrorMessage v-if="errors.webhook_url">{{ errors.webhook_url }}</ErrorMessage>
      </FormField>

      <!-- Notify Via (Checkboxes) -->
      <FormField>
        <Label>إرسال الإشعارات عبر:</Label>
        <div class="flex gap-4">
          <Checkbox
            v-model="form.notify_via"
            value="email"
            label="📧 Email"
          />
          <Checkbox
            v-model="form.notify_via"
            value="telegram"
            label="📱 Telegram"
          />
          <Checkbox
            v-model="form.notify_via"
            value="webhook"
            label="🔗 Webhook"
          />
        </div>
        <ErrorMessage v-if="errors.notify_via">{{ errors.notify_via }}</ErrorMessage>
      </FormField>

      <!-- Active Toggle -->
      <FormField>
        <Label>فعّال</Label>
        <Switch v-model="form.active" />
        <HelpText>إذا كان معطّلاً، لن يستلم هذا الأدمن أي إشعارات</HelpText>
      </FormField>

      <!-- Actions -->
      <DialogActions>
        <Button @click="showDialog = false" variant="outline">إلغاء</Button>
        <Button @click="saveAdmin" :loading="saving">{{ isEdit ? 'حفظ' : 'إضافة' }}</Button>
      </DialogActions>
    </DialogContent>
  </Dialog>
</template>
```

#### 3. **Validation على الـ Frontend**

```typescript
const validateAdmin = (admin: AdminForm): ValidationErrors => {
  const errors: ValidationErrors = {};

  // Name - مطلوب
  if (!admin.name || admin.name.trim() === '') {
    errors.name = 'الاسم مطلوب';
  }

  // Email - إذا تم اختيار email في notify_via، يجب إدخال email
  if (admin.notify_via.includes('email') && !admin.email) {
    errors.email = 'البريد الإلكتروني مطلوب عند تفعيل إشعارات Email';
  }

  // Telegram ID - إذا تم اختيار telegram في notify_via
  if (admin.notify_via.includes('telegram') && !admin.telegram_id) {
    errors.telegram_id = 'Telegram ID مطلوب عند تفعيل إشعارات Telegram';
  }

  // Webhook URL - إذا تم اختيار webhook في notify_via
  if (admin.notify_via.includes('webhook') && !admin.webhook_url) {
    errors.webhook_url = 'Webhook URL مطلوب عند تفعيل إشعارات Webhook';
  }

  // التحقق من وجود قناة واحدة على الأقل
  if (admin.notify_via.length === 0) {
    errors.notify_via = 'يجب اختيار قناة واحدة على الأقل';
  }

  return errors;
};
```

---

## ⚙️ واجهة إعدادات الإشعارات

### المكان:
تبويب (Tab) داخل صفحة Settings: **Tab 5: Notifications**

### التقسيم:

```
┌─────────────────────────────────────────────────┐
│  Settings > Notifications                       │
├─────────────────────────────────────────────────┤
│                                                  │
│  [✓] Enable Notifications (Master Toggle)       │
│                                                  │
│  Notify On:                                      │
│  ○ Success Only  ○ Failure Only  ● Both         │
│                                                  │
├─────────────────────────────────────────────────┤
│  📧 Email Notifications                          │
│  ────────────────────────────────────────────   │
│  [✓] Enable Email Notifications                 │
│                                                  │
│  Default Emails (comma-separated):              │
│  [admin1@example.com, admin2@example.com]       │
│                                                  │
│  [Test Email] ← Button                          │
│                                                  │
├─────────────────────────────────────────────────┤
│  📱 Telegram Notifications                       │
│  ────────────────────────────────────────────   │
│  [✓] Enable Telegram Notifications              │
│                                                  │
│  Bot Token:                                      │
│  [7806251613:AAEcedPSxTCib13YFa......]          │
│                                                  │
│  Default Chat IDs (comma-separated):            │
│  [111111111, 222222222, 333333333]              │
│                                                  │
├─────────────────────────────────────────────────┤
│  🔗 Webhook Notifications                        │
│  ────────────────────────────────────────────   │
│  [✓] Enable Webhook Notifications               │
│                                                  │
│  Webhook URLs (comma-separated):                │
│  [https://webhook.site/url1, ...]               │
│                                                  │
│  Webhook Secret (optional):                     │
│  [********************]                          │
│                                                  │
└─────────────────────────────────────────────────┘

                    [Save Settings]
```

### الكود المقترح:

```vue
<template>
  <div class="notifications-settings">
    <!-- Master Toggle -->
    <Card>
      <CardHeader>
        <CardTitle>تفعيل الإشعارات</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex items-center justify-between">
          <div>
            <Label>تفعيل نظام الإشعارات</Label>
            <p class="text-sm text-gray-500">
              المفتاح الرئيسي لتشغيل/إيقاف جميع الإشعارات
            </p>
          </div>
          <Switch v-model="settings.notify_enabled" />
        </div>

        <!-- Notify On -->
        <div v-if="settings.notify_enabled" class="mt-4">
          <Label>إرسال الإشعارات عند:</Label>
          <RadioGroup v-model="settings.notify_on">
            <RadioOption value="success">النجاح فقط ✅</RadioOption>
            <RadioOption value="failure">الفشل فقط ❌</RadioOption>
            <RadioOption value="both">كلاهما ✅❌</RadioOption>
          </RadioGroup>
        </div>
      </CardContent>
    </Card>

    <!-- Email Section -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>📧 إشعارات البريد الإلكتروني</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex items-center justify-between mb-4">
          <Label>تفعيل إشعارات Email</Label>
          <Switch v-model="settings.email_enabled" />
        </div>

        <div v-if="settings.email_enabled" class="space-y-4">
          <FormField>
            <Label>Emails الافتراضية (مفصولة بفاصلة)</Label>
            <Textarea
              v-model="settings.emails"
              placeholder="admin1@example.com, admin2@example.com, admin3@example.com"
              rows="2"
            />
            <HelpText>
              سيتم إرسال الإشعارات لهذه الـ Emails بشكل افتراضي، بالإضافة إلى الأدمنز المضافين في صفحة Admins
            </HelpText>
          </FormField>

          <Button @click="testEmail" variant="outline" size="sm">
            اختبار إرسال Email
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Telegram Section -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>📱 إشعارات Telegram</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex items-center justify-between mb-4">
          <Label>تفعيل إشعارات Telegram</Label>
          <Switch v-model="settings.telegram_enabled" />
        </div>

        <div v-if="settings.telegram_enabled" class="space-y-4">
          <FormField>
            <Label>Bot Token</Label>
            <Input
              v-model="settings.telegram_bot_token"
              placeholder="7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8"
              type="password"
            />
            <HelpText>
              احصل على Bot Token من @BotFather على Telegram
            </HelpText>
          </FormField>

          <FormField>
            <Label>Chat IDs الافتراضية (مفصولة بفاصلة)</Label>
            <Textarea
              v-model="settings.telegram_chat_ids"
              placeholder="111111111, 222222222, 333333333"
              rows="2"
            />
            <HelpText>
              سيتم إرسال الإشعارات لهذه الـ Chat IDs بشكل افتراضي، بالإضافة إلى الأدمنز المضافين
            </HelpText>
          </FormField>
        </div>
      </CardContent>
    </Card>

    <!-- Webhook Section -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>🔗 إشعارات Webhook</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex items-center justify-between mb-4">
          <Label>تفعيل إشعارات Webhook</Label>
          <Switch v-model="settings.webhook_enabled" />
        </div>

        <div v-if="settings.webhook_enabled" class="space-y-4">
          <FormField>
            <Label>Webhook URLs (مفصولة بفاصلة)</Label>
            <Textarea
              v-model="settings.webhook_urls"
              placeholder="https://webhook.site/url1, https://webhook.site/url2"
              rows="2"
            />
            <HelpText>
              سيتم إرسال POST request لهذه الـ URLs عند كل backup
            </HelpText>
          </FormField>

          <FormField>
            <Label>Webhook Secret (اختياري)</Label>
            <Input
              v-model="settings.webhook_secret"
              placeholder="my-secret-key"
              type="password"
            />
            <HelpText>
              سيتم إرسال HMAC signature في header X-Backup-Signature
            </HelpText>
          </FormField>
        </div>
      </CardContent>
    </Card>

    <!-- Save Button -->
    <div class="mt-6 flex justify-end">
      <Button @click="saveSettings" :loading="saving">
        حفظ الإعدادات
      </Button>
    </div>
  </div>
</template>
```

---

## 📝 TypeScript Interfaces

```typescript
// Admin Interface
export interface IBackupAdmin {
  id: number;
  name: string;
  email: string | null;        // يدعم: "admin1@test.com,admin2@test.com"
  telegram_id: string | null;  // يدعم: "123456789,987654321"
  webhook_url: string | null;  // يدعم: "https://url1.com,https://url2.com"
  notify_via: ('email' | 'telegram' | 'webhook')[];
  active: boolean;
  created_at: string;
  updated_at: string;
}

// Admin Form (للإضافة/التعديل)
export interface IAdminForm {
  name: string;
  email: string;
  telegram_id: string;
  webhook_url: string;
  notify_via: ('email' | 'telegram' | 'webhook')[];
  active: boolean;
}

// Settings Interface (الجزء المتعلق بالإشعارات فقط)
export interface INotificationSettings {
  notify_enabled: boolean;
  notify_on: 'success' | 'failure' | 'both';

  telegram_enabled: boolean;
  email_enabled: boolean;
  webhook_enabled: boolean;

  emails: string | null;              // "admin1@test.com,admin2@test.com"
  telegram_bot_token: string | null;
  telegram_chat_ids: string | null;   // "111111111,222222222"
  webhook_urls: string | null;        // "https://url1.com,https://url2.com"
  webhook_secret: string | null;
}
```

---

## 💡 أمثلة على الكود

### 1. Composable: useAdmins

```typescript
// composables/useAdmins.ts
import { ref } from 'vue';
import { adminService } from '@/services/adminService';
import type { IBackupAdmin, IAdminForm } from '@/types';

export function useAdmins() {
  const admins = ref<IBackupAdmin[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const fetchAdmins = async () => {
    loading.value = true;
    error.value = null;
    try {
      admins.value = await adminService.getAll();
    } catch (e: any) {
      error.value = e.message;
    } finally {
      loading.value = false;
    }
  };

  const createAdmin = async (data: IAdminForm) => {
    loading.value = true;
    error.value = null;
    try {
      const newAdmin = await adminService.create(data);
      admins.value.push(newAdmin);
      return newAdmin;
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const updateAdmin = async (id: number, data: IAdminForm) => {
    loading.value = true;
    error.value = null;
    try {
      const updated = await adminService.update(id, data);
      const index = admins.value.findIndex(a => a.id === id);
      if (index !== -1) {
        admins.value[index] = updated;
      }
      return updated;
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const deleteAdmin = async (id: number) => {
    loading.value = true;
    error.value = null;
    try {
      await adminService.delete(id);
      admins.value = admins.value.filter(a => a.id !== id);
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    admins,
    loading,
    error,
    fetchAdmins,
    createAdmin,
    updateAdmin,
    deleteAdmin,
  };
}
```

### 2. Service: adminService

```typescript
// services/adminService.ts
import api from './api';
import type { IBackupAdmin, IAdminForm } from '@/types';

export const adminService = {
  async getAll(): Promise<IBackupAdmin[]> {
    const { data } = await api.get('/backup/admins');
    return data;
  },

  async create(admin: IAdminForm): Promise<IBackupAdmin> {
    const { data } = await api.post('/backup/admins', admin);
    return data;
  },

  async update(id: number, admin: IAdminForm): Promise<IBackupAdmin> {
    const { data } = await api.put(`/backup/admins/${id}`, admin);
    return data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/backup/admins/${id}`);
  },
};
```

### 3. Helper: تقسيم وعرض القيم المفصولة بفاصلة

```typescript
// utils/helpers.ts

/**
 * تقسيم النص المفصول بفاصلة إلى array
 */
export function splitByComma(value: string | null): string[] {
  if (!value) return [];
  return value
    .split(',')
    .map(item => item.trim())
    .filter(item => item.length > 0);
}

/**
 * دمج array إلى نص مفصول بفاصلة
 */
export function joinByComma(items: string[]): string {
  return items
    .map(item => item.trim())
    .filter(item => item.length > 0)
    .join(', ');
}

/**
 * عرض القيم المفصولة بفاصلة بشكل مختصر
 * مثال: "admin1@test.com, admin2@test.com, +3 more"
 */
export function displayCommaSeparated(value: string | null, maxItems: number = 2): string {
  if (!value) return '-';

  const items = splitByComma(value);

  if (items.length === 0) return '-';
  if (items.length <= maxItems) return items.join(', ');

  const displayed = items.slice(0, maxItems).join(', ');
  const remaining = items.length - maxItems;

  return `${displayed}, +${remaining} more`;
}
```

**مثال استخدام:**

```vue
<template>
  <TableCell>
    {{ displayCommaSeparated(admin.email, 2) }}
  </TableCell>
</template>

<script setup lang="ts">
import { displayCommaSeparated } from '@/utils/helpers';
</script>
```

**Output:**
```
admin1@test.com, admin2@test.com, +3 more
```

---

## ✅ Validation Rules

### على مستوى الـ Backend (للمراجعة فقط):

```php
// BackupAdminRequest
[
    'name' => ['required', 'string', 'max:100'],
    'email' => ['nullable', 'string', 'max:500'],
    'telegram_id' => ['nullable', 'string', 'max:200'],
    'webhook_url' => ['nullable', 'string', 'max:1000'],
    'active' => ['boolean'],
    'notify_via' => ['array'],
    'notify_via.*' => ['in:telegram,email,webhook'],
]
```

### على مستوى الـ Frontend:

```typescript
// validation.ts
export const adminValidationRules = {
  name: [
    { required: true, message: 'الاسم مطلوب' },
    { max: 100, message: 'الاسم طويل جداً (الحد الأقصى 100 حرف)' }
  ],

  email: [
    {
      validator: (value: string, formData: any) => {
        if (formData.notify_via.includes('email') && !value) {
          return 'البريد الإلكتروني مطلوب عند تفعيل إشعارات Email';
        }
        return true;
      }
    },
    { max: 500, message: 'النص طويل جداً' }
  ],

  telegram_id: [
    {
      validator: (value: string, formData: any) => {
        if (formData.notify_via.includes('telegram') && !value) {
          return 'Telegram ID مطلوب عند تفعيل إشعارات Telegram';
        }
        return true;
      }
    },
    { max: 200, message: 'النص طويل جداً' }
  ],

  webhook_url: [
    {
      validator: (value: string, formData: any) => {
        if (formData.notify_via.includes('webhook') && !value) {
          return 'Webhook URL مطلوب عند تفعيل إشعارات Webhook';
        }
        return true;
      }
    },
    { max: 1000, message: 'النص طويل جداً' }
  ],

  notify_via: [
    {
      validator: (value: string[]) => {
        if (value.length === 0) {
          return 'يجب اختيار قناة واحدة على الأقل';
        }
        return true;
      }
    }
  ]
};
```

---

## 🎨 UI/UX Tips

### 1. عرض القيم المتعددة في الجدول:

```vue
<!-- Bad ❌ -->
<TableCell>
  admin1@test.com,admin2@test.com,admin3@test.com
</TableCell>

<!-- Good ✅ -->
<TableCell>
  <div class="flex flex-wrap gap-1">
    <Badge v-for="email in splitByComma(admin.email)" :key="email">
      {{ email }}
    </Badge>
  </div>
</TableCell>

<!-- Better ✅✅ (للقيم الكثيرة) -->
<TableCell>
  <Tooltip>
    <TooltipTrigger>
      {{ displayCommaSeparated(admin.email, 2) }}
    </TooltipTrigger>
    <TooltipContent>
      <div v-for="email in splitByComma(admin.email)" :key="email">
        {{ email }}
      </div>
    </TooltipContent>
  </Tooltip>
</TableCell>
```

### 2. Notify Via Badges:

```vue
<div class="flex gap-1">
  <Badge v-if="admin.notify_via.includes('email')" variant="blue">
    📧 Email
  </Badge>
  <Badge v-if="admin.notify_via.includes('telegram')" variant="sky">
    📱 Telegram
  </Badge>
  <Badge v-if="admin.notify_via.includes('webhook')" variant="purple">
    🔗 Webhook
  </Badge>
</div>
```

### 3. Active Toggle في الجدول:

```vue
<TableCell>
  <Switch
    :model-value="admin.active"
    @update:model-value="toggleActive(admin.id, $event)"
    size="sm"
  />
</TableCell>
```

### 4. Confirmation قبل الحذف:

```typescript
const confirmDelete = async (admin: IBackupAdmin) => {
  const confirmed = await showConfirmDialog({
    title: 'حذف الأدمن',
    message: `هل أنت متأكد من حذف "${admin.name}"؟ لن يستلم إشعارات بعد الحذف.`,
    confirmText: 'حذف',
    confirmVariant: 'destructive'
  });

  if (confirmed) {
    await deleteAdmin(admin.id);
    showToast({ message: 'تم حذف الأدمن بنجاح', variant: 'success' });
  }
};
```

---

## 📊 مثال على الـ State Management (Pinia)

```typescript
// stores/admin.ts
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { adminService } from '@/services/adminService';
import type { IBackupAdmin, IAdminForm } from '@/types';

export const useAdminStore = defineStore('admin', () => {
  const admins = ref<IBackupAdmin[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const fetchAdmins = async () => {
    loading.value = true;
    error.value = null;
    try {
      admins.value = await adminService.getAll();
    } catch (e: any) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  const createAdmin = async (data: IAdminForm) => {
    const admin = await adminService.create(data);
    admins.value.push(admin);
    return admin;
  };

  const updateAdmin = async (id: number, data: IAdminForm) => {
    const updated = await adminService.update(id, data);
    const index = admins.value.findIndex(a => a.id === id);
    if (index !== -1) {
      admins.value[index] = updated;
    }
    return updated;
  };

  const deleteAdmin = async (id: number) => {
    await adminService.delete(id);
    admins.value = admins.value.filter(a => a.id !== id);
  };

  const toggleActive = async (id: number, active: boolean) => {
    const admin = admins.value.find(a => a.id === id);
    if (!admin) return;

    await updateAdmin(id, {
      name: admin.name,
      email: admin.email || '',
      telegram_id: admin.telegram_id || '',
      webhook_url: admin.webhook_url || '',
      notify_via: admin.notify_via,
      active: active
    });
  };

  return {
    admins,
    loading,
    error,
    fetchAdmins,
    createAdmin,
    updateAdmin,
    deleteAdmin,
    toggleActive,
  };
});
```

---

## 🧪 Testing Checklist

### Tests للواجهة:

- [ ] إضافة أدمن جديد بقيم مفردة (email واحد، telegram ID واحد)
- [ ] إضافة أدمن بقيم متعددة (عدة emails، عدة telegram IDs)
- [ ] تعديل بيانات أدمن موجود
- [ ] حذف أدمن
- [ ] تفعيل/تعطيل أدمن عبر Toggle
- [ ] فلترة القائمة حسب Notify Via
- [ ] فلترة القائمة حسب Active
- [ ] البحث بالاسم
- [ ] التحقق من Validation (عدم السماح بحفظ بدون اسم)
- [ ] التحقق من Validation (عدم السماح بتفعيل Email بدون إدخال email)
- [ ] عرض القيم المتعددة بشكل صحيح في الجدول
- [ ] حفظ إعدادات الإشعارات
- [ ] تفعيل/تعطيل كل قناة على حدة
- [ ] اختبار إرسال Email

---

## 📚 Resources

- **Vue 3 Docs:** https://vuejs.org/
- **Pinia:** https://pinia.vuejs.org/
- **TypeScript:** https://www.typescriptlang.org/
- **Tailwind CSS:** https://tailwindcss.com/

---

## ✅ Checklist قبل التسليم

- [ ] جميع الـ API calls تعمل بشكل صحيح
- [ ] Validation على Frontend يعمل
- [ ] Error Handling موجود في كل API call
- [ ] Loading States موجودة في كل عملية غير متزامنة
- [ ] Success/Error Messages تظهر للمستخدم
- [ ] الواجهة Responsive (Mobile, Tablet, Desktop)
- [ ] الكود منظم ونظيف (Clean Code)
- [ ] TypeScript Interfaces معرّفة بشكل صحيح
- [ ] Composables/Stores منظمة
- [ ] Helper Functions معرّفة ومستخدمة بشكل صحيح

---

**تاريخ التحديث:** 2025-11-05

**النسخة:** 1.0
