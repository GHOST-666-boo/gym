<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a category and product for testing
        $this->category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $this->category->id]);
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /** @test */
    public function guests_can_submit_reviews()
    {
        $reviewData = [
            'reviewer_name' => 'John Doe',
            'reviewer_email' => 'john@example.com',
            'rating' => 5,
            'title' => 'Great product!',
            'comment' => 'This gym machine is excellent. Highly recommended!'
        ];

        $response = $this->post(route('reviews.store', $this->product), $reviewData);

        $response->assertRedirect(route('products.show', $this->product->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'reviewer_name' => 'John Doe',
            'reviewer_email' => 'john@example.com',
            'rating' => 5,
            'title' => 'Great product!',
            'comment' => 'This gym machine is excellent. Highly recommended!',
            'is_approved' => false,
            'user_id' => null
        ]);
    }

    /** @test */
    public function authenticated_users_can_submit_reviews()
    {
        $reviewData = [
            'reviewer_name' => $this->user->name,
            'reviewer_email' => $this->user->email,
            'rating' => 4,
            'title' => 'Good quality',
            'comment' => 'Solid build quality and good value for money.'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('reviews.store', $this->product), $reviewData);

        $response->assertRedirect(route('products.show', $this->product->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'reviewer_name' => $this->user->name,
            'reviewer_email' => $this->user->email,
            'rating' => 4,
            'is_approved' => false
        ]);
    }

    /** @test */
    public function review_submission_requires_valid_data()
    {
        $response = $this->post(route('reviews.store', $this->product), []);

        $response->assertSessionHasErrors([
            'reviewer_name',
            'reviewer_email',
            'rating',
            'title',
            'comment'
        ]);
    }

    /** @test */
    public function review_rating_must_be_between_1_and_5()
    {
        $reviewData = [
            'reviewer_name' => 'John Doe',
            'reviewer_email' => 'john@example.com',
            'rating' => 6, // Invalid rating
            'title' => 'Test review',
            'comment' => 'Test comment'
        ];

        $response = $this->post(route('reviews.store', $this->product), $reviewData);
        $response->assertSessionHasErrors(['rating']);

        $reviewData['rating'] = 0; // Invalid rating
        $response = $this->post(route('reviews.store', $this->product), $reviewData);
        $response->assertSessionHasErrors(['rating']);
    }

    /** @test */
    public function only_approved_reviews_are_displayed_on_product_page()
    {
        // Create approved and pending reviews
        $approvedReview = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        $pendingReview = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $response = $this->get(route('products.show', $this->product->slug));

        $response->assertStatus(200);
        $response->assertSee($approvedReview->title);
        $response->assertSee($approvedReview->comment);
        $response->assertDontSee($pendingReview->title);
        $response->assertDontSee($pendingReview->comment);
    }

    /** @test */
    public function product_displays_correct_average_rating()
    {
        // Create multiple approved reviews with different ratings
        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 5,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 3,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 4,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        // Create a pending review that shouldn't affect the average
        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 1,
            'is_approved' => false
        ]);

        $this->product->refresh();
        
        // Average should be (5 + 3 + 4) / 3 = 4.0
        $this->assertEquals(4.0, $this->product->average_rating);
        $this->assertEquals(3, $this->product->reviews_count);
    }

    /** @test */
    public function admin_can_view_reviews_index()
    {
        Review::factory()->count(3)->create(['product_id' => $this->product->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reviews.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reviews.index');
    }

    /** @test */
    public function non_admin_cannot_access_admin_reviews()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.reviews.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_individual_review()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reviews.show', $review));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reviews.show');
        $response->assertSee($review->title);
        $response->assertSee($review->comment);
    }

    /** @test */
    public function admin_can_approve_review()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reviews.approve', $review));

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        $review->refresh();
        $this->assertTrue($review->is_approved);
        $this->assertNotNull($review->approved_at);
        $this->assertEquals($this->admin->id, $review->approved_by);
    }

    /** @test */
    public function admin_can_reject_review()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reviews.reject', $review));

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        $review->refresh();
        $this->assertFalse($review->is_approved);
        $this->assertNull($review->approved_at);
        $this->assertNull($review->approved_by);
    }

    /** @test */
    public function admin_can_delete_review()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.reviews.destroy', $review));

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function admin_can_bulk_approve_reviews()
    {
        $reviews = Review::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $reviewIds = $reviews->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reviews.bulk-approve'), [
                'review_ids' => $reviewIds
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        foreach ($reviews as $review) {
            $review->refresh();
            $this->assertTrue($review->is_approved);
            $this->assertNotNull($review->approved_at);
            $this->assertEquals($this->admin->id, $review->approved_by);
        }
    }

    /** @test */
    public function admin_can_bulk_reject_reviews()
    {
        $reviews = Review::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        $reviewIds = $reviews->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reviews.bulk-reject'), [
                'review_ids' => $reviewIds
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        foreach ($reviews as $review) {
            $review->refresh();
            $this->assertFalse($review->is_approved);
            $this->assertNull($review->approved_at);
            $this->assertNull($review->approved_by);
        }
    }

    /** @test */
    public function admin_can_bulk_delete_reviews()
    {
        $reviews = Review::factory()->count(3)->create(['product_id' => $this->product->id]);
        $reviewIds = $reviews->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reviews.bulk-delete'), [
                'review_ids' => $reviewIds
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');

        foreach ($reviewIds as $reviewId) {
            $this->assertDatabaseMissing('reviews', ['id' => $reviewId]);
        }
    }

    /** @test */
    public function admin_can_filter_reviews_by_status()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'title' => 'Approved Review'
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false,
            'title' => 'Pending Review'
        ]);

        // Test pending filter
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reviews.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Pending Review');
        $response->assertDontSee('Approved Review');

        // Test approved filter
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reviews.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('Approved Review');
        $response->assertDontSee('Pending Review');
    }

    /** @test */
    public function admin_can_search_reviews()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'title' => 'Excellent Product',
            'reviewer_name' => 'John Smith'
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'title' => 'Poor Quality',
            'reviewer_name' => 'Jane Doe'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reviews.index', ['search' => 'Excellent']));

        $response->assertStatus(200);
        $response->assertSee('Excellent Product');
        $response->assertDontSee('Poor Quality');
    }

    /** @test */
    public function reviews_ajax_endpoint_returns_paginated_reviews()
    {
        Review::factory()->count(15)->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        $response = $this->getJson(route('reviews.index', $this->product));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'reviews',
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]
        ]);

        $data = $response->json();
        $this->assertCount(10, $data['reviews']); // Default pagination is 10
        $this->assertEquals(15, $data['pagination']['total']);
    }
}