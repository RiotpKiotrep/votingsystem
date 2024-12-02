<?php
# This file is only meant for hash generation, and is kept for the purpose of showcase.
# In the real version, this file should be deleted.

echo password_hash("admin", PASSWORD_DEFAULT)."<br>"; # $2y$10$kFZcVcoBJjEstNWQmOSwue.pTy2Vi2QcaKadLurwrNXkg1snC9DX6
echo password_hash("Admin12345", PASSWORD_DEFAULT); # $2y$10$IGoD0pr8yuuqYG5uPIojNOwwfXeFpEPdmU5kCfLN6J1EzHMcvUNLi

?>