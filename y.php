<?php
// 1. جلب رقم القناة من الرابط (URL) باستخدام $_GET
$channel_id = isset($_GET['id']) ? $_GET['id'] : 4; // في حال لم يتم تقديم الرقم، سيتم استخدام القيمة الافتراضية 4

// 2. تكوين الرابط باستخدام الرقم الديناميكي
$url = "https://a502.variety-buy.store/api/channel/" . $channel_id;

// 3. جلب البيانات المشفرة من واجهة برمجة التطبيقات
$ch = curl_init();
$headers = [
    "Accept: application/json",
    "User-Agent: okhttp/3.12.8"
];
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, true); // لتمكين جلب الرؤوس
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

// 4. استخراج قيمة الرأس 't'
preg_match('/^t:\s*(.*)$/mi', $headers, $matches);
if (!isset($matches[1])) {
    die("لم يتم العثور على الرأس 't' في الاستجابة.");
}
$t_value = trim($matches[1]);

// 5. تكوين مفتاح التشفير
$key = "c!xZj+N9&G@Ev@vw" . $t_value;

// 6. فك تشفير البيانات
try {
    $decoded_data = base64_decode($body);
    if ($decoded_data === false) {
        throw new Exception("فشل في فك تشفير Base64.");
    }

    $key_length = strlen($key);
    $decrypted_data = '';
    for ($i = 0, $len = strlen($decoded_data); $i < $len; $i++) {
        $decrypted_data .= $decoded_data[$i] ^ $key[$i % $key_length];
    }

    // 7. استبدال \/ بـ /
    $decrypted_data = str_replace('\/', '/', $decrypted_data);

    // 8. تحويل البيانات المفكوكة من JSON
    $data = json_decode($decrypted_data, true);
    if ($data === null) {
        die("فشل في تحليل البيانات كـ JSON.");
    }

    // 9. استخراج الرابط الأول
    if (isset($data['data'][0]['url'])) {
        $first_url = $data['data'][0]['url'];

        // 10. إعادة توجيه المستخدم إلى الرابط الأول (تشغيل الفيديو مباشرة)
        header('Location: ' . $first_url);
        exit;

    } else {
        echo "لم يتم العثور على الرابط في البيانات.";
    }

} catch (Exception $e) {
    echo "حدث خطأ أثناء فك التشفير: " . $e->getMessage();
}

?>
