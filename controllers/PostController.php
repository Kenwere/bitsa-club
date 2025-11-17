<?php
require_once '../models/Post.php';
require_once '../includes/auth.php';

class PostController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function getAllPosts() {
        $postModel = new Post();
        $posts = $postModel->getAll();
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post);
        }

        return $formattedPosts;
    }

    public function getUserPosts($user_id) {
        $postModel = new Post();
        $posts = $postModel->getUserPosts($user_id);
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post);
        }

        return $formattedPosts;
    }

    private function formatPost($post) {
        $user = $this->auth->getCurrentUser();
        $isLiked = false;

        // Check if current user liked this post
        if ($user) {
            $postModel = new Post();
            // You'll need to implement a method to check if user liked the post
            // $isLiked = $postModel->isPostLikedByUser($post['id'], $user['id']);
        }

        return [
            'id' => $post['id'],
            'user' => [
                'name' => $post['user_name'],
                'username' => $post['username'] ?? 'user',
                'avatar' => strtoupper(substr($post['user_name'], 0, 2))
            ],
            'content' => $post['content'] ?? '',
            'image' => $post['image'] ? '../assets/uploads/' . $post['image'] : null,
            'created_at' => $post['created_at'],
            'likes' => $post['likes_count'] ?? 0,
            'comments' => $this->getPostComments($post['id']),
            'liked' => $isLiked
        ];
    }

    private function getPostComments($post_id) {
        $postModel = new Post();
        $comments = $postModel->getComments($post_id);
        
        $formattedComments = [];
        foreach ($comments as $comment) {
            $formattedComments[] = [
                'id' => $comment['id'],
                'user' => [
                    'name' => $comment['user_name'],
                    'username' => $comment['username']
                ],
                'content' => $comment['content'],
                'created_at' => $comment['created_at']
            ];
        }

        return $formattedComments;
    }
}
?>