<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $this->category->id]);
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /** @test */
    public function review_belongs_to_product()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id]);

        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertEquals($this->product->id, $review->product->id);
    }

    /** @test */
    public function review_belongs_to_user()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($this->user->id, $review->user->id);
    }

    /** @test */
    public function review_can_have_null_user_for_guest_reviews()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => null
        ]);

        $this->assertNull($review->user);
    }

    /** @test */
    public function review_belongs_to_approved_by_user()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_by' => $this->admin->id,
            'approved_at' => now()
        ]);

        $this->assertInstanceOf(User::class, $review->approvedBy);
        $this->assertEquals($this->admin->id, $review->approvedBy->id);
    }

    /** @test */
    public function approved_scope_returns_only_approved_reviews()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $approvedReviews = Review::approved()->get();

        $this->assertCount(1, $approvedReviews);
        $this->assertTrue($approvedReviews->first()->is_approved);
    }

    /** @test */
    public function pending_scope_returns_only_pending_reviews()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $pendingReviews = Review::pending()->get();

        $this->assertCount(1, $pendingReviews);
        $this->assertFalse($pendingReviews->first()->is_approved);
    }

    /** @test */
    public function by_rating_scope_filters_by_rating()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 5
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 3
        ]);

        $fiveStarReviews = Review::byRating(5)->get();
        $threeStarReviews = Review::byRating(3)->get();

        $this->assertCount(1, $fiveStarReviews);
        $this->assertCount(1, $threeStarReviews);
        $this->assertEquals(5, $fiveStarReviews->first()->rating);
        $this->assertEquals(3, $threeStarReviews->first()->rating);
    }

    /** @test */
    public function star_rating_attribute_returns_formatted_stars()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 4
        ]);

        $expected = '★★★★☆';
        $this->assertEquals($expected, $review->star_rating);
    }

    /** @test */
    public function is_approved_method_returns_correct_boolean()
    {
        $approvedReview = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true
        ]);

        $pendingReview = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false
        ]);

        $this->assertTrue($approvedReview->isApproved());
        $this->assertFalse($pendingReview->isApproved());
    }

    /** @test */
    public function approve_method_updates_review_status()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null
        ]);

        $review->approve($this->admin);

        $this->assertTrue($review->is_approved);
        $this->assertNotNull($review->approved_at);
        $this->assertEquals($this->admin->id, $review->approved_by);
    }

    /** @test */
    public function reject_method_updates_review_status()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $this->admin->id
        ]);

        $review->reject();

        $this->assertFalse($review->is_approved);
        $this->assertNull($review->approved_at);
        $this->assertNull($review->approved_by);
    }

    /** @test */
    public function review_casts_attributes_correctly()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'is_approved' => 1, // Should be cast to boolean
            'rating' => '4', // Should be cast to integer
            'approved_at' => '2024-01-01 12:00:00' // Should be cast to datetime
        ]);

        $this->assertIsBool($review->is_approved);
        $this->assertIsInt($review->rating);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $review->approved_at);
    }

    /** @test */
    public function review_has_correct_fillable_attributes()
    {
        $review = new Review();
        
        $expectedFillable = [
            'product_id',
            'user_id',
            'reviewer_name',
            'reviewer_email',
            'rating',
            'title',
            'comment',
            'is_approved',
            'approved_at',
            'approved_by',
        ];

        $this->assertEquals($expectedFillable, $review->getFillable());
    }
}