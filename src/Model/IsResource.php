<?php

namespace Witify\Support\Model;

interface IsResource
{
    public function getResourceTitle(): string;

    public function getResourceSubtitle(): ?string;

    public static function getResourceIcon(): string;

    public static function getResourceColor(): string;

    public function getResourceAdminTo(): string;
}
