<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\PostCommented;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Comments under a post (FR-10).
 */
class CommentController extends Controller
{
    /**
     * Write a comment on a post.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse|RedirectResponse
    {
        // StoreCommentRequest authorizes against PostPolicy, which also stops
        // people from commenting on hidden posts (FR-28).
        $validated = $request->validated();

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // The author is needed to render the new comment card.
        $comment->load('user:id,name,role,photo');

        // The post author hears about the comment - unless they wrote it
        // themselves (FR-23).
        if ($post->user_id !== $request->user()->id) {
            $post->user?->notify(new PostCommented($request->user(), $post, $comment));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('comments._comment', ['comment' => $comment])->render(),
                'count' => $post->comments()->visible()->count(),
            ], 201);
        }

        return back()->with('success', __('Your comment has been added.'));
    }

    /**
     * Delete a comment. CommentPolicy checks the ownership (FR-10).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $comment);

        $post = $comment->post;
        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'deleted' => true,
                'count' => $post ? $post->comments()->visible()->count() : 0,
            ]);
        }

        return back()->with('success', __('The comment has been deleted.'));
    }
}
