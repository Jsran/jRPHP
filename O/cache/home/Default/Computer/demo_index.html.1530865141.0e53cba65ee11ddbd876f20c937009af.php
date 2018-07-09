<?php if(!class_exists("\jR\V", false)) exit("no direct access allowed");?><!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
__THEMES__
<?php /* 我是常量*/?>
<?php echo M; ?>
<?php /* 我是函数*/?>
<?php echo dump('asdsa');?>
<?php echo htmlspecialchars($run, ENT_QUOTES, "UTF-8"); ?>
</body>
</html>