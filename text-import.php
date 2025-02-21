<style>
    table{
        border-collapse:collapse;
        border-spacing:0;
        border:1px solid #ccc;
        margin:10px 0;
    }
    table th,table td{
        border:1px solid #ccc;
        padding:5px;
    }
    .header{
        text-align:center;
    }
</style>

<?php
if(!empty($_FILES['file'])){
    move_uploaded_file($_FILES['file']['tmp_name'], "./files/{$_FILES['file']['name']}");
    echo $_FILES['file']['name']."上傳成功";
    getfile("./files/{$_FILES['file']['name']}");
}

function getfile($path){
    try {
        $conn = new PDO("mysql:host=localhost", 'root', '');
        $conn->exec("CREATE DATABASE IF NOT EXISTS import");
        $conn->exec("USE import");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tableName = 'table_' . mt_rand(100000, 999999);
        $file = fopen($path, 'r');

        $header_cols = fgetcsv($file);

        // 處理BOM問題
        $header_cols[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header_cols[0]);

        $header_cols = array_map(function($col) {
            $clean_col = preg_replace("/[^a-zA-Z0-9_]/u", "", trim($col));
            return empty($clean_col) ? 'col'.mt_rand(1000,9999) : $clean_col; // 避免空欄位名稱
        }, $header_cols);

        $tmpcols = [];
        foreach ($header_cols as $hc) {
            $tmpcols[] = "`$hc` TEXT NOT NULL";
        }

        $sql = "CREATE TABLE `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,";
        $sql .= join(",", $tmpcols);
        $sql .= ")";
        $conn->exec($sql);

        $stmt = $conn->prepare("INSERT INTO `$tableName` (`".join("`,`",$header_cols)."`) values(".str_repeat("?,",count($header_cols)-1)."?)");
        
        $count = 0;
        while (($cols = fgetcsv($file)) !== false) {
            $cols = array_slice($cols, 0, count($header_cols));
            $stmt->execute($cols);
            $count++;
        }
        fclose($file);
        echo "資料匯入 $tableName 完成，共匯入 $count 筆資料";

    } catch(PDOException $e) {
        echo "錯誤: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>文字檔案匯入</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1 class="header">文字檔案匯入練習</h1>
<form action="?" method="post" enctype="multipart/form-data">
    <label for="file">文字檔:</label><input type="file" name="file" id="file">
    <input type="submit" value="上傳">
</form>
</body>
</html>
