import { Menu, X, ShoppingCart, User, LogOut } from 'lucide-react';
import { useState } from 'react';
import { useNavigate, useLocation } from 'react-router';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { LoginModal } from './LoginModal';

export function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
  const [showCartDropdown, setShowCartDropdown] = useState(false);
  const { isAuthenticated, user, logout, isAdmin } = useAuth();
  const { cart, getTotalItems, removeFromCart } = useCart();
  const navigate = useNavigate();
  const location = useLocation();

  const isOnLandingPage = location.pathname === '/';

  const scrollToSection = (id: string) => {
    if (!isOnLandingPage) {
      navigate('/');
      setTimeout(() => {
        const element = document.getElementById(id);
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' });
        }
      }, 100);
    } else {
      const element = document.getElementById(id);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
      }
    }
    setIsMenuOpen(false);
  };

  const navigateToProducts = () => {
    navigate('/products');
    setIsMenuOpen(false);
  };

  const navigateHome = () => {
    navigate('/');
    setIsMenuOpen(false);
  };

  const navigateToAdmin = () => {
    navigate('/admin');
    setIsMenuOpen(false);
  };

  const handleAuthClick = () => {
    if (isAuthenticated) {
      logout();
    } else {
      setIsLoginModalOpen(true);
    }
  };

  return (
    <>
      <header className="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md shadow-sm z-50 border-b border-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center shadow-lg">
                <span className="text-white text-2xl">M</span>
              </div>
              <div>
                <div className="text-2xl text-primary tracking-tight">MOTOTRACK</div>
                <div className="text-xs text-muted-foreground hidden sm:block">Edmar Motorcycle Parts</div>
              </div>
            </div>

            {/* Desktop Navigation */}
            <nav className="hidden lg:flex items-center gap-6">
              <button
                onClick={navigateHome}
                className="text-foreground hover:text-primary transition-colors"
              >
                Home
              </button>
              <button
                onClick={() => scrollToSection('about')}
                className="text-foreground hover:text-primary transition-colors"
              >
                About
              </button>
              <button
                onClick={navigateToProducts}
                className="text-foreground hover:text-primary transition-colors"
              >
                Products
              </button>
              <button
                onClick={() => scrollToSection('compatibility')}
                className="text-foreground hover:text-primary transition-colors"
              >
                Compatibility
              </button>
              <button
                onClick={() => scrollToSection('preorder')}
                className="text-foreground hover:text-primary transition-colors"
              >
                Pre-Order
              </button>
              <button
                onClick={() => scrollToSection('contact')}
                className="text-foreground hover:text-primary transition-colors"
              >
                Contact
              </button>

              {/* Admin Panel Link */}
              {isAdmin && (
                <button
                  onClick={navigateToAdmin}
                  className="text-foreground hover:text-primary transition-colors flex items-center gap-1"
                >
                  Admin Panel
                </button>
              )}

              {/* Cart Icon */}
              <div className="relative">
                <button
                  onClick={() => setShowCartDropdown(!showCartDropdown)}
                  className="relative p-2 text-foreground hover:text-primary transition-colors"
                >
                  <ShoppingCart size={24} />
                  {getTotalItems() > 0 && (
                    <span className="absolute -top-1 -right-1 bg-accent text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                      {getTotalItems()}
                    </span>
                  )}
                </button>

                {/* Cart Dropdown */}
                {showCartDropdown && (
                  <div className="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-border p-4 max-h-96 overflow-y-auto">
                    {cart.length === 0 ? (
                      <div className="text-center py-8">
                        <ShoppingCart className="mx-auto text-gray-400 mb-2" size={48} />
                        <p className="text-muted-foreground">Your cart is empty</p>
                      </div>
                    ) : (
                      <>
                        <div className="space-y-3 mb-4">
                          {cart.map((item) => (
                            <div key={item.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                              <div className="w-12 h-12 bg-gray-200 rounded-lg flex-shrink-0"></div>
                              <div className="flex-1 min-w-0">
                                <div className="text-sm text-foreground truncate">{item.name}</div>
                                <div className="text-xs text-muted-foreground">
                                  ₱{item.price} × {item.quantity}
                                </div>
                              </div>
                              <button
                                onClick={() => removeFromCart(item.id)}
                                className="text-red-500 hover:text-red-700 text-xs"
                              >
                                Remove
                              </button>
                            </div>
                          ))}
                        </div>
                        <div className="border-t border-border pt-3">
                          <div className="flex justify-between items-center mb-3">
                            <span className="text-foreground">Total:</span>
                            <span className="text-xl text-primary">
                              ₱{cart.reduce((sum, item) => sum + item.price * item.quantity, 0)}
                            </span>
                          </div>
                          <button className="w-full bg-gradient-to-r from-primary to-accent text-white py-3 rounded-xl hover:shadow-lg transition-all">
                            Checkout
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                )}
              </div>

              {/* User Account */}
              <button
                onClick={handleAuthClick}
                className="flex items-center gap-2 bg-gradient-to-r from-primary to-accent text-white px-4 py-2.5 rounded-xl hover:shadow-lg hover:scale-105 transition-all"
              >
                {isAuthenticated ? (
                  <>
                    <User size={18} />
                    <span className="hidden xl:inline">{user?.name}</span>
                    <LogOut size={16} />
                  </>
                ) : (
                  <>
                    <User size={18} />
                    <span>Sign In</span>
                  </>
                )}
              </button>
            </nav>

            {/* Mobile Menu Button */}
            <div className="flex items-center gap-3 lg:hidden">
              {/* Mobile Cart */}
              <button
                onClick={() => setShowCartDropdown(!showCartDropdown)}
                className="relative p-2 text-foreground"
              >
                <ShoppingCart size={24} />
                {getTotalItems() > 0 && (
                  <span className="absolute -top-1 -right-1 bg-accent text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                    {getTotalItems()}
                  </span>
                )}
              </button>

              <button
                onClick={() => setIsMenuOpen(!isMenuOpen)}
                className="text-primary"
              >
                {isMenuOpen ? <X size={24} /> : <Menu size={24} />}
              </button>
            </div>
          </div>

          {/* Mobile Navigation */}
          {isMenuOpen && (
            <nav className="lg:hidden py-4 border-t border-border">
              <div className="flex flex-col gap-4">
                <button
                  onClick={navigateHome}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  Home
                </button>
                <button
                  onClick={() => scrollToSection('about')}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  About
                </button>
                <button
                  onClick={navigateToProducts}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  Products
                </button>
                <button
                  onClick={() => scrollToSection('compatibility')}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  Compatibility
                </button>
                <button
                  onClick={() => scrollToSection('preorder')}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  Pre-Order
                </button>
                <button
                  onClick={() => scrollToSection('contact')}
                  className="text-foreground hover:text-primary transition-colors text-left"
                >
                  Contact
                </button>
                {isAdmin && (
                  <button
                    onClick={navigateToAdmin}
                    className="text-foreground hover:text-primary transition-colors text-left"
                  >
                    Admin Panel
                  </button>
                )}
                <button
                  onClick={handleAuthClick}
                  className="bg-gradient-to-r from-primary to-accent text-white px-6 py-2.5 rounded-xl hover:shadow-lg transition-all flex items-center gap-2 justify-center"
                >
                  {isAuthenticated ? (
                    <>
                      <User size={18} />
                      {user?.name}
                      <LogOut size={16} />
                    </>
                  ) : (
                    <>
                      <User size={18} />
                      Sign In
                    </>
                  )}
                </button>
              </div>
            </nav>
          )}
        </div>
      </header>

      <LoginModal isOpen={isLoginModalOpen} onClose={() => setIsLoginModalOpen(false)} />
    </>
  );
}