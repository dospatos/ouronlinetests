<!DOCTYPE html>
<html>
<head>
	<title><?php if(isset($title)) echo $title; ?></title>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
					
	<!-- Controller Specific JS/CSS -->
	<?php if(isset($client_files_head)) echo $client_files_head; ?>
    <link rel="stylesheet" href="/css/basic-minimal.css" type="text/css"/>
    <link rel="stylesheet" href="/css/jquery-ui.css" type="text/css"/>
    <script type="text/javascript" src="/js/jquery-1.10.2.js"></script>
    <script type="text/javascript" src="/js/jquery-ui.js"></script>
    <script type="text/javascript" src="/js/jquery.ui.core.js"></script>
    <script type="text/javascript" src="/js/jquery.ui.widget.js"></script>
    <script type="text/javascript" src="/js/jquery.ui.timer.js"></script>

</head>

<body>

<div id='menu'>

    <a href='/'>Home</a> |

    <!-- Menu for users who are logged in -->
    <?php if($user): ?>
        <a href='/users/profileedit/<?php echo $user->user_id?>'>Your Account</a> |
        <a href="/users/testtakers">Test Takers |
        <a href="/tests">Tests |
        <a href='/users/logout'>Logout</a>

        <!-- Menu options for users who are not logged in -->
    <?php else: ?>

        <a href='/users/signup'>Sign up</a> |
        <a href='/users/login'>Log in</a>

    <?php endif; ?>

</div>

<br>

<?php if(isset($content)) echo $content; ?>

</body>
</html>