import { Header } from '../components/Header';
import { Footer } from '../components/Footer';
import { ShoppingCart, Star, Search, SlidersHorizontal, Grid3x3, List } from 'lucide-react';
import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { LoginModal } from '../components/LoginModal';

interface Product {
  id: string;
  name: string;
  price: number;
  image: string;
  category: string;
  brand: string;
  rating: number;
  reviews: number;
  inStock: boolean;
  discount?: number;
}

const products: Product[] = [
  {
    id: '1',
    name: 'Premium Air Filter',
    price: 350,
    image: 'placeholder',
    category: 'Engine Parts',
    brand: 'Honda',
    rating: 4.5,
    reviews: 128,
    inStock: true,
  },
  {
    id: '2',
    name: 'Ceramic Brake Pads Set',
    price: 580,
    image: 'placeholder',
    category: 'Brake System',
    brand: 'Yamaha',
    rating: 5,
    reviews: 256,
    inStock: true,
    discount: 10,
  },
  {
    id: '3',
    name: 'Iridium Spark Plug',
    price: 120,
    image: 'placeholder',
    category: 'Engine Parts',
    brand: 'Universal',
    rating: 4,
    reviews: 89,
    inStock: true,
  },
  {
    id: '4',
    name: 'Heavy Duty Chain Set',
    price: 850,
    image: 'placeholder',
    category: 'Drive Train',
    brand: 'Suzuki',
    rating: 4.5,
    reviews: 176,
    inStock: true,
  },
  {
    id: '5',
    name: 'High Flow Oil Filter',
    price: 180,
    image: 'placeholder',
    category: 'Engine Parts',
    brand: 'Honda',
    rating: 4.5,
    reviews: 203,
    inStock: true,
  },
  {
    id: '6',
    name: 'Aerodynamic Side Mirror',
    price: 420,
    image: 'placeholder',
    category: 'Accessories',
    brand: 'Universal',
    rating: 4,
    reviews: 94,
    inStock: true,
  },
  {
    id: '7',
    name: 'AGM Battery 12V 7Ah',
    price: 1200,
    image: 'placeholder',
    category: 'Electrical',
    brand: 'Motolite',
    rating: 5,
    reviews: 312,
    inStock: true,
    discount: 15,
  },
  {
    id: '8',
    name: 'LED Headlight Bulb H4',
    price: 250,
    image: 'placeholder',
    category: 'Electrical',
    brand: 'Universal',
    rating: 4,
    reviews: 157,
    inStock: false,
  },
  {
    id: '9',
    name: 'Performance Clutch Plate',
    price: 650,
    image: 'placeholder',
    category: 'Engine Parts',
    brand: 'Yamaha',
    rating: 4.5,
    reviews: 143,
    inStock: true,
  },
  {
    id: '10',
    name: 'Stainless Steel Exhaust',
    price: 2500,
    image: 'placeholder',
    category: 'Exhaust System',
    brand: 'Universal',
    rating: 5,
    reviews: 89,
    inStock: true,
  },
  {
    id: '11',
    name: 'Racing CDI Unit',
    price: 1800,
    image: 'placeholder',
    category: 'Electrical',
    brand: 'Performance',
    rating: 4.5,
    reviews: 267,
    inStock: true,
    discount: 20,
  },
  {
    id: '12',
    name: 'Front Fork Oil Seal',
    price: 320,
    image: 'placeholder',
    category: 'Suspension',
    brand: 'Honda',
    rating: 4,
    reviews: 112,
    inStock: true,
  },
];

const categories = ['All Categories', 'Engine Parts', 'Brake System', 'Drive Train', 'Electrical', 'Accessories', 'Exhaust System', 'Suspension'];
const brands = ['All Brands', 'Honda', 'Yamaha', 'Suzuki', 'Universal', 'Performance', 'Motolite'];
const sortOptions = ['Featured', 'Price: Low to High', 'Price: High to Low', 'Best Rating', 'Most Reviews'];

export function ProductsPage() {
  const [selectedCategory, setSelectedCategory] = useState('All Categories');
  const [selectedBrand, setSelectedBrand] = useState('All Brands');
  const [sortBy, setSortBy] = useState('Featured');
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
  const { isAuthenticated } = useAuth();
  const { addToCart } = useCart();

  const filteredProducts = products
    .filter(product => {
      const matchesCategory = selectedCategory === 'All Categories' || product.category === selectedCategory;
      const matchesBrand = selectedBrand === 'All Brands' || product.brand === selectedBrand;
      const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase());
      return matchesCategory && matchesBrand && matchesSearch;
    })
    .sort((a, b) => {
      switch (sortBy) {
        case 'Price: Low to High':
          return a.price - b.price;
        case 'Price: High to Low':
          return b.price - a.price;
        case 'Best Rating':
          return b.rating - a.rating;
        case 'Most Reviews':
          return b.reviews - a.reviews;
        default:
          return 0;
      }
    });

  const handleAddToCart = (product: Product) => {
    if (!isAuthenticated) {
      setIsLoginModalOpen(true);
      return;
    }
    addToCart({
      id: product.id,
      name: product.name,
      price: product.price,
      image: product.image,
    });
  };

  return (
    <>
      <div className="min-h-screen bg-background">
        <Header />
        
        <main className="pt-24 pb-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {/* Breadcrumb */}
            <div className="mb-6 text-sm text-muted-foreground">
              <a href="/" className="hover:text-primary">Home</a>
              <span className="mx-2">/</span>
              <span className="text-foreground">Products</span>
            </div>

            {/* Page Header */}
            <div className="mb-8">
              <h1 className="text-3xl md:text-4xl text-foreground mb-4">Motorcycle Parts Catalog</h1>
              <p className="text-muted-foreground">Browse our wide selection of quality motorcycle parts and accessories</p>
            </div>

            {/* Search & Controls Bar */}
            <div className="bg-white rounded-2xl shadow-lg border border-border p-4 mb-6">
              <div className="flex flex-col md:flex-row gap-4">
                {/* Search */}
                <div className="flex-1 relative">
                  <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-muted-foreground" size={20} />
                  <input
                    type="text"
                    placeholder="Search products..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full pl-12 pr-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  />
                </div>

                {/* Sort */}
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors min-w-[200px]"
                >
                  {sortOptions.map((option) => (
                    <option key={option} value={option}>{option}</option>
                  ))}
                </select>

                {/* View Mode */}
                <div className="flex gap-2">
                  <button
                    onClick={() => setViewMode('grid')}
                    className={`p-3 rounded-xl border-2 transition-all ${
                      viewMode === 'grid'
                        ? 'bg-primary text-white border-primary'
                        : 'border-border text-muted-foreground hover:border-primary'
                    }`}
                  >
                    <Grid3x3 size={20} />
                  </button>
                  <button
                    onClick={() => setViewMode('list')}
                    className={`p-3 rounded-xl border-2 transition-all ${
                      viewMode === 'list'
                        ? 'bg-primary text-white border-primary'
                        : 'border-border text-muted-foreground hover:border-primary'
                    }`}
                  >
                    <List size={20} />
                  </button>
                </div>

                {/* Mobile Filter Toggle */}
                <button
                  onClick={() => setIsFilterOpen(!isFilterOpen)}
                  className="lg:hidden flex items-center gap-2 px-4 py-3 border-2 border-border rounded-xl hover:border-primary transition-colors"
                >
                  <SlidersHorizontal size={20} />
                  Filters
                </button>
              </div>
            </div>

            <div className="flex gap-6">
              {/* Sidebar Filters */}
              <aside className={`${isFilterOpen ? 'block' : 'hidden'} lg:block w-full lg:w-64 flex-shrink-0`}>
                <div className="bg-white rounded-2xl shadow-lg border border-border p-6 sticky top-24">
                  <div className="flex items-center justify-between mb-6">
                    <h3 className="text-lg text-foreground">Filters</h3>
                    <SlidersHorizontal className="text-primary" size={20} />
                  </div>

                  {/* Category Filter */}
                  <div className="mb-6">
                    <h4 className="text-sm text-foreground mb-3">Category</h4>
                    <div className="space-y-2">
                      {categories.map((category) => (
                        <button
                          key={category}
                          onClick={() => setSelectedCategory(category)}
                          className={`w-full text-left px-3 py-2 rounded-lg transition-all ${
                            selectedCategory === category
                              ? 'bg-blue-50 text-primary border-2 border-primary'
                              : 'text-muted-foreground hover:bg-gray-50'
                          }`}
                        >
                          {category}
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Brand Filter */}
                  <div className="mb-6">
                    <h4 className="text-sm text-foreground mb-3">Brand</h4>
                    <div className="space-y-2">
                      {brands.map((brand) => (
                        <button
                          key={brand}
                          onClick={() => setSelectedBrand(brand)}
                          className={`w-full text-left px-3 py-2 rounded-lg transition-all ${
                            selectedBrand === brand
                              ? 'bg-blue-50 text-primary border-2 border-primary'
                              : 'text-muted-foreground hover:bg-gray-50'
                          }`}
                        >
                          {brand}
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Price Range Placeholder */}
                  <div className="mb-6">
                    <h4 className="text-sm text-foreground mb-3">Price Range</h4>
                    <div className="space-y-2">
                      <button className="w-full text-left px-3 py-2 rounded-lg text-muted-foreground hover:bg-gray-50">
                        Under ₱500
                      </button>
                      <button className="w-full text-left px-3 py-2 rounded-lg text-muted-foreground hover:bg-gray-50">
                        ₱500 - ₱1,000
                      </button>
                      <button className="w-full text-left px-3 py-2 rounded-lg text-muted-foreground hover:bg-gray-50">
                        ₱1,000 - ₱2,000
                      </button>
                      <button className="w-full text-left px-3 py-2 rounded-lg text-muted-foreground hover:bg-gray-50">
                        Over ₱2,000
                      </button>
                    </div>
                  </div>

                  <button className="w-full bg-gradient-to-r from-primary to-accent text-white py-3 rounded-xl hover:shadow-lg transition-all">
                    Apply Filters
                  </button>
                </div>
              </aside>

              {/* Products Grid/List */}
              <div className="flex-1">
                <div className="mb-4 text-sm text-muted-foreground">
                  Showing {filteredProducts.length} products
                </div>

                {/* Grid View */}
                {viewMode === 'grid' && (
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredProducts.map((product) => (
                      <div
                        key={product.id}
                        className="bg-white rounded-2xl shadow-lg border border-border hover:shadow-2xl transition-all group overflow-hidden"
                      >
                        {/* Product Image */}
                        <div className="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative overflow-hidden">
                          <svg className="w-24 h-24 text-gray-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,7H18V6A2,2 0 0,0 16,4H8A2,2 0 0,0 6,6V7H5A2,2 0 0,0 3,9V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V9A2,2 0 0,0 19,7M8,6H16V7H8V6M19,20H5V9H19V20Z"/>
                          </svg>
                          {!product.inStock && (
                            <div className="absolute top-2 right-2 bg-red-500 text-white text-xs px-3 py-1 rounded-full">
                              Out of Stock
                            </div>
                          )}
                          {product.discount && (
                            <div className="absolute top-2 left-2 bg-accent text-white text-xs px-3 py-1 rounded-full">
                              -{product.discount}%
                            </div>
                          )}
                        </div>

                        {/* Product Info */}
                        <div className="p-5">
                          <div className="text-xs text-muted-foreground mb-1">{product.brand} · {product.category}</div>
                          <h3 className="text-lg text-foreground mb-2 line-clamp-2">{product.name}</h3>
                          
                          {/* Rating */}
                          <div className="flex items-center gap-1 mb-3">
                            {[...Array(5)].map((_, i) => (
                              <Star
                                key={i}
                                size={14}
                                className={i < Math.floor(product.rating) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'}
                              />
                            ))}
                            <span className="text-xs text-muted-foreground ml-1">({product.reviews})</span>
                          </div>

                          <div className="flex items-baseline gap-2 mb-4">
                            {product.discount ? (
                              <>
                                <div className="text-2xl text-primary">₱{Math.round(product.price * (1 - product.discount / 100))}</div>
                                <div className="text-sm text-muted-foreground line-through">₱{product.price}</div>
                              </>
                            ) : (
                              <div className="text-2xl text-primary">₱{product.price}</div>
                            )}
                          </div>

                          <button
                            onClick={() => handleAddToCart(product)}
                            disabled={!product.inStock}
                            className={`w-full py-3 rounded-xl transition-all flex items-center justify-center gap-2 ${
                              product.inStock
                                ? 'bg-gradient-to-r from-primary to-accent text-white hover:shadow-lg hover:scale-[1.02]'
                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                            }`}
                          >
                            <ShoppingCart size={18} />
                            {product.inStock ? 'Add to Cart' : 'Out of Stock'}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* List View */}
                {viewMode === 'list' && (
                  <div className="space-y-4">
                    {filteredProducts.map((product) => (
                      <div
                        key={product.id}
                        className="bg-white rounded-2xl shadow-lg border border-border hover:shadow-2xl transition-all overflow-hidden"
                      >
                        <div className="flex flex-col sm:flex-row gap-6 p-6">
                          {/* Product Image */}
                          <div className="w-full sm:w-48 aspect-square bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative overflow-hidden rounded-xl flex-shrink-0">
                            <svg className="w-24 h-24 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                              <path d="M19,7H18V6A2,2 0 0,0 16,4H8A2,2 0 0,0 6,6V7H5A2,2 0 0,0 3,9V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V9A2,2 0 0,0 19,7M8,6H16V7H8V6M19,20H5V9H19V20Z"/>
                            </svg>
                            {product.discount && (
                              <div className="absolute top-2 left-2 bg-accent text-white text-xs px-3 py-1 rounded-full">
                                -{product.discount}%
                              </div>
                            )}
                          </div>

                          {/* Product Details */}
                          <div className="flex-1 flex flex-col justify-between">
                            <div>
                              <div className="text-xs text-muted-foreground mb-1">{product.brand} · {product.category}</div>
                              <h3 className="text-xl text-foreground mb-2">{product.name}</h3>
                              
                              {/* Rating */}
                              <div className="flex items-center gap-1 mb-3">
                                {[...Array(5)].map((_, i) => (
                                  <Star
                                    key={i}
                                    size={16}
                                    className={i < Math.floor(product.rating) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'}
                                  />
                                ))}
                                <span className="text-sm text-muted-foreground ml-2">
                                  {product.rating} ({product.reviews} reviews)
                                </span>
                              </div>

                              <div className="flex items-center gap-2 mb-2">
                                {product.inStock ? (
                                  <span className="text-green-600 text-sm">In Stock</span>
                                ) : (
                                  <span className="text-red-600 text-sm">Out of Stock</span>
                                )}
                              </div>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-4">
                              <div className="flex items-baseline gap-2">
                                {product.discount ? (
                                  <>
                                    <div className="text-3xl text-primary">₱{Math.round(product.price * (1 - product.discount / 100))}</div>
                                    <div className="text-lg text-muted-foreground line-through">₱{product.price}</div>
                                  </>
                                ) : (
                                  <div className="text-3xl text-primary">₱{product.price}</div>
                                )}
                              </div>

                              <button
                                onClick={() => handleAddToCart(product)}
                                disabled={!product.inStock}
                                className={`px-8 py-3 rounded-xl transition-all flex items-center gap-2 ${
                                  product.inStock
                                    ? 'bg-gradient-to-r from-primary to-accent text-white hover:shadow-lg hover:scale-[1.02]'
                                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                }`}
                              >
                                <ShoppingCart size={18} />
                                {product.inStock ? 'Add to Cart' : 'Out of Stock'}
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* Empty State */}
                {filteredProducts.length === 0 && (
                  <div className="text-center py-20 bg-white rounded-2xl shadow-lg border border-border">
                    <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                      <ShoppingCart className="text-gray-400" size={48} />
                    </div>
                    <h3 className="text-2xl text-foreground mb-2">No products found</h3>
                    <p className="text-muted-foreground">Try adjusting your filters or search query</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </main>

        <Footer />
      </div>

      <LoginModal isOpen={isLoginModalOpen} onClose={() => setIsLoginModalOpen(false)} />
    </>
  );
}
