<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ilsawn — Translations</title>
    @livewireStyles
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon"
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAEIElEQVR4AexVaSjnXRR+mmmmsWZLElJCJB9kzZJsX1CW7EuWki2ULYkiJR+ULcs3SxnerImy5AMhWcuWJSKSrTdLlrK8nhsT/Zm/MW9NTX517nLuufc899zznN+nu7u7f+/+4PcJf/j7APARgd+OwM3NDTY2Nt6dyu8CQIffm76juLgYBQUF8PLyegZidHQUa2trbwL1SwBGRkbg4eEBW1tbNNQ3CCcTExPY3d1FdHQ0Li8vcXh4CH9/f6Smpv5/AO7rFPLz8xESEgJHR0fMz8+jp6cHVVVVyMnJQUtLC/r7+/Ht2zc0NTVBXl4eY2NjWF5elgriTRHIzc1Fe3s7ent7kZaWBhUVlR8HW1lZwcHBAZ8/f8bt7S3q6+sRFxcHCwsL1Dc0/LB7bSAVAJ02NjaKWxoaGqKvrw/Nzc0v3m5oaAg7OzsIDg5GeHg4/rm3u7i4eM230EsFwDDm5eVBT08PJSUl4HP4+fmhq6sLzAmesre3JwBWVlaCrLCxsUFWVhaOj4/R2dlJk1dFKoCkpCSEhoaKA3R1daGvrw86io2NxdHRkcj+oqIikAnZ2dlwd3dHW1sbOjo6EBQUhLq6OrH3tUYCwMrKCpaWloQ9b/B4SypWV1chJycHExMTkWjU0SYmJgbl5eW4urqCp6cnTE1NhSQkJGBqagoLCws0fVEkAHR3dwsanZ+fY25uDt7e3mLj9PS04Pzm5qaYs7m+vsbk5CRmZ2fB6Ojo6ODk5IRLQgwMDMDn+FkUJABERESId2b/WEwGBgYQGRkJMzMzWFpaisPHx8dFIpLzTk5OqKioEPRk8gmDh6bhnglkzsNUopMAoKysjNbWVhwcHCAjI0NsIP+tra0RFRUl5sPDw5CRkRGJxhDzmfgMLi4uYv1po6ioCHV19aeqZ2MJAFwl3QYHB8W7cs4Q19TUiMgQGJONPZPR2NhYJCATkraPwjxydnYWrHjUvdR/eklJKtXW1qK6ulosp6enC8qFhYWBoWfls7e3h7a2Nra3t0VdIPeF8UNTWloKIyMjUaAeVC92EgDIc1KM3NfU1BSbWOV8fHzAgsR/AYUgZmZmoKCggMDAQCgpKQlbNqwRLFiZmZmc/lQkALDGkwl0wJLK3cxiHsbisr+/D1lZWVHtWKKZ6bR5FDpn7SgrKxMRetS/1ksAOD09Fclnbm4ukicgIEDsTUxMBKOwtbUl5myYfOvr6xxicXERjFxycrLIHUZJLEhpJAAwnCkpKWIbb8cCw8nXr19Rcl+KCezs7Ay+vr6iRjBK1Lm5ueHLly/g/+CtznmuBAAqpQl/t+S2hoYG7OzsUFhYKCJAsFpaWtK2P1t/FwCewApHqsbHx8PV1RXkO/W/Ku8GQEdqampQVVXl8N3yWwDe7fXJxg8Af38Enjz3i8P/AAAA//8uoJYpAAAABklEQVQDAIxKL891J423AAAAAElFTkSuQmCC">

</head>

<body class="bg-gray-100 text-gray-900 antialiased">
    {{ $slot }}
    @livewireScripts
</body>

</html>
