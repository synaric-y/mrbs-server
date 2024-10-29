<?php

echo <<<EOD

<?xml version="1.0" encoding="UTF-8"?>
<response>
    <status>
        <code>200</code>
        <message>Success</message>
    </status>
    <data>
        <user>
            <id>12345</id>
            <name>John Doe</name>
            <email>johndoe@example.com</email>
        </user>
        <order>
            <id>98765</id>
            <product>Widget</product>
            <quantity>3</quantity>
            <price>29.99</price>
        </order>
    </data>
</response>

EOD;
