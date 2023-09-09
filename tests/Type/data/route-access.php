<?php

route('existing'); // Exists, no error here
route('existingg'); // Typo -> should be caught as not existing and be suggested as a fix
route('abcdefghjkl'); // Simply does not exist. No alternative should be suggested
