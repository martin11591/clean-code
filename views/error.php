<?php

$this->prepend('title', "Error {$code} - ");

if ($messages) echo "Error messages:<br />\r\n" . implode("<br />\r\n", $messages);
