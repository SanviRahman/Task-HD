# ইনস্টলেশন নির্দেশনা

## নতুন ইনস্টল (XAMPP, PHP 8.1+, MySQL)
1. ZIP extract করে `JV_Construction_Management_System` ফোল্ডারটি `C:\xampp\htdocs\`-এ রাখুন।
2. XAMPP-এ Apache ও MySQL চালু করুন।
3. `config.php`-এ database host, name, user, password ঠিক করুন।
4. ব্রাউজারে `http://localhost/JV_Construction_Management_System/install.php` খুলে অ্যাডমিন তৈরি করুন।
5. phpMyAdmin > SQL থেকে **প্রথমে** `upgrade.sql`, **তারপর** `ledger_upgrade.sql` চালান।
6. `login.php` থেকে লগইন করুন। `install.php` মুছে দিন।

## পুরোনো ইনস্টল আপগ্রেড
1. সব ফাইল ও ডেটাবেসের সম্পূর্ণ ব্যাকআপ নিন।
2. পুরোনো অ্যাপের ফাইল নতুন ZIP-এর ফাইল দিয়ে প্রতিস্থাপন করুন; `config.php`-এর আগের ডেটাবেস সেটিংস বজায় রাখুন।
3. যদি আগে চালানো না হয়ে থাকে, ক্রমানুসারে `upgrade.sql`, `ledger_upgrade.sql` চালান। ইতিমধ্যে upgrade.sql চালানো থাকলে আবার চালাবেন না (duplicate column error হতে পারে)।
4. Admin > Users/Projects থেকে কর্মীদের প্রজেক্টে assign করুন। Expense Heads থেকে প্রজেক্টের নিজস্ব খাত তৈরি করুন।
5. Advances-এ নির্ধারিত কর্মীকে অগ্রিম দিন, Expenses-এ একই কর্মী ও প্রজেক্টের খাতে ব্যয় দিন, Admin অনুমোদন করুন।
6. Employee Ledger-এ running balance এবং Project Account Report-এ তারিখভিত্তিক সারাংশ দেখুন। প্রিন্ট ডায়ালগে Save as PDF নির্বাচন করুন।

**সতর্কতা:** লাইভ সার্ভারে ব্যবহারের আগে ডেটাবেসে পরীক্ষা, নিরাপত্তা নিরীক্ষা ও নিয়মিত ব্যাকআপ জরুরি। এটি ব্রাউজার PDF প্রিন্ট, সরাসরি PDF ফাইল জেনারেটর নয়।
