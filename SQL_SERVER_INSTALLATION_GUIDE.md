# 📥 دليل تثبيت SQL Server Driver for PHP 8.3.26

## ⚠️ ملاحظة مهمة
PHP 8.3 قد لا يكون مدعوماً بشكل رسمي من Microsoft SQL Server Driver. 
الحلول المتاحة:

## ✅ الحل الأول: استخدام PHP 8.2 (موصى به)

1. **قم بتحميل PHP 8.2 من Laragon:**
   - افتح Laragon
   - اضغط على "Menu" → "PHP" → "Version"
   - اختر PHP 8.2.x (64-bit TS)

2. **تحميل SQL Server Driver:**
   - رابط التحميل: https://github.com/Microsoft/msphpsql/releases
   - ابحث عن آخر إصدار يدعم PHP 8.2
   - أو استخدم: https://pecl.php.net/package/sqlsrv

## ✅ الحل الثاني: تثبيت يدوي لـ PHP 8.3

### الخطوة 1: تحميل الملفات

**الخيار أ: من GitHub (موصى به)**
```
https://github.com/Microsoft/msphpsql/releases/latest
```

**الخيار ب: من PECL**
```
https://pecl.php.net/package/sqlsrv
```

**ملاحظة:** إذا لم تجد إصدار لـ PHP 8.3، استخدم إصدار PHP 8.2 - عادة ما يعمل مع PHP 8.3

### الخطوة 2: تحديد الملفات المطلوبة

لـ PHP 8.3.26 (64-bit TS) تحتاج:
- `php_sqlsrv_83_ts_x64.dll` أو `php_sqlsrv_82_ts_x64.dll`
- `php_pdo_sqlsrv_83_ts_x64.dll` أو `php_pdo_sqlsrv_82_ts_x64.dll`

### الخطوة 3: نسخ الملفات

انسخ الملفات إلى:
```
C:/laragon/bin/php/php-8.3.26-Win32-vs16-x64/ext
```

### الخطوة 4: تعديل php.ini

افتح الملف:
```
C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.ini
```

أضف هذه الأسطر في نهاية الملف:
```ini
; SQL Server Extensions
extension=php_sqlsrv_83_ts_x64.dll
extension=php_pdo_sqlsrv_83_ts_x64.dll
```

**ملاحظة:** إذا كانت أسماء الملفات مختلفة، استخدم الأسماء الفعلية للملفات التي قمت بتحميلها.

### الخطوة 5: تثبيت ODBC Driver

تحميل Microsoft ODBC Driver for SQL Server:
```
https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server
```

اختر: **ODBC Driver 17 for SQL Server** أو **ODBC Driver 18 for SQL Server**

### الخطوة 6: إعادة تشغيل Laragon

1. اضغط على "Stop All" في Laragon
2. انتظر قليلاً
3. اضغط على "Start All"
4. أو أعد تشغيل Laragon بالكامل

### الخطوة 7: التحقق من التثبيت

افتح Terminal في Laragon واكتب:
```bash
php -m | findstr sqlsrv
```

يجب أن ترى:
```
pdo_sqlsrv
sqlsrv
```

أو افتح `test-connection.php` واضغط "Test Connection"

## ✅ الحل الثالث: استخدام FreeTDS (بديل)

إذا لم تعمل الحلول السابقة، يمكنك استخدام FreeTDS:

1. تحميل FreeTDS: http://www.freetds.org/
2. تثبيت FreeTDS
3. استخدام `pdo_dblib` بدلاً من `pdo_sqlsrv`

## 🔍 التحقق من المشاكل

### إذا ظهرت رسالة "Unable to load dynamic library"
- تأكد من أن أسماء الملفات صحيحة في php.ini
- تأكد من أن الملفات موجودة في مجلد ext
- تأكد من أن PHP 8.3 مدعوم (قد تحتاج PHP 8.2)

### إذا ظهرت رسالة "Class 'PDO' not found"
- تأكد من تفعيل `extension=pdo` في php.ini

### إذا ظهرت رسالة "SQLSTATE[IMSSP]"
- تأكد من تثبيت ODBC Driver
- تأكد من أن SQL Server يعمل ويمكن الوصول إليه

## 📞 روابط مفيدة

- Microsoft SQL Server Driver: https://github.com/Microsoft/msphpsql
- ODBC Driver: https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server
- PHP Manual: https://www.php.net/manual/en/book.sqlsrv.php

---

**تم إنشاء هذا الملف بواسطة: Prompt Manager Platform**

