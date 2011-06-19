Please move the folder "wire" to your current bp theme


1.To enable group wire, please put following lines of code in your bp-custom.php

add_filter("is_group_wire_enabled","enable_group_wire");


function enable_group_wire(){

return true;
}

2.Using language file, please put the mo file in bp-wire/languages/bp-wire_YourLang.mo
3. Changing label for wire, either use language file or use the following ocde in bp-custom.php
define("BP_WIRE_LABEL,"Your wire label");

4. Changing the position of wire, please put following code in the bp-custom.php
define("BP_WIRE_POSITION",20);//or what ever you want