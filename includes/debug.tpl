<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$title}!!</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1,user-scalable=no">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <style>
        body {
            padding: 100px;
            color: #333;
            font-size: 14px;
        }

        .oops {
            width: 100%;
            text-align: left;
        }

        h1 {
            font-size: 2em;
        }

        th, td {
            background: #eeeeec;
            padding: 5px;
        }
        small{
            color: #c9302c;
        }
        th.warning {
            background: #e9b96e;
        }
        .cell-n {
            text-align: center;
        }
        @media screen and (max-width: 900px) {
            body {
                padding: 20px 5px;
            }

            small {
                display: block;
            }
            .cell-f,.cell-n{
                display: none;
            }
        }
    </style>
</head>
<body>
<h1>{$title}!!
    <small>{$message}</small>
</h1>

<table class="oops">
    <tbody>
    <tr>
        <th colspan="3" class="warning">{$cs} - {$uri}</th>
    </tr>
    <tr>
        <th class="cell-n">#</th>
        <th class="cell-f">{$f}</th>
        <th>{$l}</th>
    </tr>
    {$stackInfo}
    </tbody>
</table>
</body>
</html>