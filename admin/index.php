<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin</title>
<script>

const token = localStorage.getItem('token');

if(token){
    window.location.href = "dashboard";
}else{
    window.location.href = "login";
}

</script>
</head>
<body></body>
</html>