<?php

namespace Whis\Database\Migrations;

interface Migration
{
    public function up();

    public function down();
}
