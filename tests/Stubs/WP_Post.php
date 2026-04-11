<?php

class WP_Post
{
    public int $ID = 0;
    public string $post_type = 'post';
    public string $post_title = '';
    public string $post_status = '';

    public static function create(array $props = []): self
    {
        $post = new self();

        foreach ($props as $key => $value) {
            $post->$key = $value;
        }

        return $post;
    }
}
