<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_name' => 'required|string|max:255',
            'reviewer_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reviewer_name.required' => 'Please enter your name.',
            'reviewer_name.max' => 'Name cannot be longer than 255 characters.',
            'reviewer_email.required' => 'Please enter your email address.',
            'reviewer_email.email' => 'Please enter a valid email address.',
            'reviewer_email.max' => 'Email cannot be longer than 255 characters.',
            'rating.required' => 'Please select a rating.',
            'rating.integer' => 'Rating must be a number.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot be more than 5 stars.',
            'title.required' => 'Please enter a review title.',
            'title.max' => 'Title cannot be longer than 255 characters.',
            'comment.required' => 'Please enter your review comment.',
            'comment.max' => 'Comment cannot be longer than 1000 characters.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reviewer_name' => 'name',
            'reviewer_email' => 'email',
            'rating' => 'rating',
            'title' => 'review title',
            'comment' => 'review comment',
        ];
    }
}
