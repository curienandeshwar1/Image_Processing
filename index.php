<?php session_start(); ?>
<html>
<head><title>Hello app</title>
</head>
<style>
body{ margin: 3px; text-align: left ; font-family: Arial; }
</style>
<body style="background-color: #f5ccff">
<div align="left">
<br/>
<br/>
<br/>
<h3>UPLOAD IMAGE</h3>
<br/>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="submit.php" method="POST">
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
    <!-- Name of input element determines name in $_FILES array -->
    Send this file : <input name="userfile" type="file" /><br />
    <br />
    Name : <input type="text" name="name"><br />
    <br />
    User Email : <input type="email" name="useremail"><br />
    <br />
    User Phone (1-XXX-XXX-XXXX) : <input type="phone" name="phone"><br />
    <br />

<input type="submit" value="Submit" />
</form>
<hr />
<br/>
<br/>
<br/>
<h3>GALLERY</h3>
<br/>
<form enctype="multipart/form-data" action="gallery.php" method="POST">
Enter Email of user for gallery to browse : <input type="email" name="useremail">
<input type="submit" value="Load Gallery" />
</form>


</div>
</body>
</html>
