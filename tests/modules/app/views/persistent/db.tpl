<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>mysql 持久化链接性能测试</title>
</head>
<body>
    <table border="1">
        <tr>
            <td>ID</td>
            <td>Age</td>
        </tr>
        {foreach $rows as $row}
            <tr>
                <td>{$row.id}</td>
                <td>{$row.age}</td>
            </tr>
        {/foreach}
    </table>
</body>
</html>