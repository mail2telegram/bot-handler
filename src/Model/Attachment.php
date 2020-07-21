<?php

namespace M2T\Model;

class Attachment
{
    public string $name;
    public string $content;

    public function __construct(string $name, string $content)
    {
        $this->name = $name;
        $this->content = $content;
    }
}
