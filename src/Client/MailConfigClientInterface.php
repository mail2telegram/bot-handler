<?php

namespace M2T\Client;

interface MailConfigClientInterface
{


    /**
     * Возвращает почтовую информацию об указанном домене
     * @param string $domain
     * @return array
     */
    public function get(string $domain): array;

}
