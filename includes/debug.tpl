<html>
<head>
    <meta charset="utf-8">
    <title>{$title}!!</title>
</head>
<body style="padding: 100px;">
<h1>{$title}!!</h1>

<table class="oops" cellspacing="0" cellpadding="1" border="1" width="100%">
    <tbody>
    <tr>
        <th colspan="3" bgcolor="#f57900" align="left">
            <span style="background-color: #cc0000; color: #fce94f; font-size: x-large;">( ! )</span>
            {$tip}: {$message}
        </th>
    </tr>
    <tr>
        <th colspan="3" bgcolor="#e9b96e" align="left">{$cs}: {$uri}</th>
    </tr>
    <tr>
        <th bgcolor="#eeeeec" align="center">#</th>
        <th bgcolor="#eeeeec" align="left">{$f}</th>
        <th bgcolor="#eeeeec" align="left">{$l}</th>
    </tr>
    {$stackInfo}
    </tbody>
</table>
</body>
</html>