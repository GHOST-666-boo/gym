@extends('layouts.admin')

@section('title', 'Delete Category')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Delete Category</h1>
            <p class="text-gray-600 mt-1">Confirm category deletion and product reassignment</p>
        </div>
        <a href="{{ route('admin.categories.show', $category) }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Cancel
        </a>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <!-- Warning Alert -->
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Warning: This category contains products
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>
                            The category "{{ $category->name }}" contains {{ $category->products_count }} 
                            {{ Str::plural('product', $category->products_count) }}. 
                            You must decide what to do with these products before deleting the category.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Information -->
        <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ $category->name }}</h3>
                    <p class="text-gray-600">
                        {{ $category->products_count }} {{ Str::plural('product', $category->products_count) }} • 
                        Created {{ $category->created_at->format('M j, Y') }}
                    </p>
                </div>
            </div>

            @if($category->description)
                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                    <strong>Description:</strong> {{ $category->description }}
                </div>
            @endif
        </div>

        <!-- Products Preview -->
        @if($products->count() > 0)
            <div class="mb-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">
                        Products in this Category
                        @if($category->products_count > $products->count())
                            <span class="text-sm text-gray-500">
                                (Showing {{ $products->count() }} of {{ $category->products_count }})
                            </span>
                        @endif
                    </h4>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($products as $product)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                @if($product->image_path)
                                    <img class="h-10 w-10 rounded object-cover" 
                                         src="{{ asset('storage/' . $product->image_path) }}" 
                                         alt="{{ $product->name }}">
                                @else
                                    <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="ml-3 flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                                    <p class="text-sm text-gray-500">${{ number_format($product->price, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($category->products_count > $products->count())
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500">
                                ... and {{ $category->products_count - $products->count() }} more products
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Deletion Options Form -->
        <form action="{{ route('admin.categories.reassign-delete', $category) }}" method="POST" class="bg-white border border-gray-200 rounded-lg p-6">
            @csrf

            <h4 class="text-lg font-medium text-gray-900 mb-4">Choose what to do with the products:</h4>

            <div class="space-y-4">
                <!-- Option 1: Move to Uncategorized -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="uncategorize" 
                               name="action" 
                               type="radio" 
                               value="uncategorize"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                               checked>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="uncategorize" class="font-medium text-gray-700">
                            Move products to "Uncategorized"
                        </label>
                        <p class="text-gray-500">
                            Products will remain on the website but won't belong to any category. 
                            You can assign them to other categories later.
                        </p>
                    </div>
                </div>

                <!-- Option 2: Reassign to Another Category -->
                @if($otherCategories->count() > 0)
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="reassign" 
                                   name="action" 
                                   type="radio" 
                                   value="reassign"
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        </div>
                        <div class="ml-3 text-sm flex-1">
                            <label for="reassign" class="font-medium text-gray-700">
                                Move products to another category
                            </label>
                            <p class="text-gray-500 mb-3">
                                Select an existing category to move all products to:
                            </p>
                            <select name="reassign_to" 
                                    id="reassign_to"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                    disabled>
                                <option value="">Select a category...</option>
                                @foreach($otherCategories as $otherCategory)
                                    <option value="{{ $otherCategory->id }}">{{ $otherCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>No other categories available.</strong> 
                                    Products can only be moved to "Uncategorized" or you can 
                                    <a href="{{ route('admin.categories.create') }}" class="underline hover:text-yellow-800">create a new category</a> 
                                    first.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Confirmation -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="confirm" 
                               name="confirm" 
                               type="checkbox" 
                               required
                               class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="confirm" class="font-medium text-gray-700">
                            I understand that this action cannot be undone
                        </label>
                        <p class="text-gray-500">
                            The category "{{ $category->name }}" will be permanently deleted from the system.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.categories.show', $category) }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                    Delete Category
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Enable/disable the reassign_to select based on radio button selection
        const reassignRadio = document.getElementById('reassign');
        const uncategorizeRadio = document.getElementById('uncategorize');
        const reassignSelect = document.getElementById('reassign_to');

        function toggleReassignSelect() {
            if (reassignRadio && reassignRadio.checked) {
                reassignSelect.disabled = false;
                reassignSelect.required = true;
            } else {
                reassignSelect.disabled = true;
                reassignSelect.required = false;
                reassignSelect.value = '';
            }
        }

        if (reassignRadio) {
            reassignRadio.addEventListener('change', toggleReassignSelect);
        }
        if (uncategorizeRadio) {
            uncategorizeRadio.addEventListener('change', toggleReassignSelect);
        }

        // Initial state
        toggleReassignSelect();
    </script>
    @endpush
@endsection