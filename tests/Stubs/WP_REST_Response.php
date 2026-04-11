<?php

class WP_REST_Response
{
    protected $data;
    protected int $status;

    public function __construct($data = null, int $status = 200)
    {
        $this->data   = $data;
        $this->status = $status;
    }

    public function get_data()
    {
        return $this->data;
    }

    public function get_status(): int
    {
        return $this->status;
    }
}
