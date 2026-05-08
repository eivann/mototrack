import { X } from 'lucide-react';
import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router';

interface LoginModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function LoginModal({ isOpen, onClose }: LoginModalProps) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    login(email, password);

    // Redirect based on user role
    if (email === 'admin@mototrack.com') {
      navigate('/admin');
    } else {
      navigate('/products');
    }

    onClose();
    setEmail('');
    setPassword('');
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 relative animate-in fade-in zoom-in duration-300">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
        >
          <X size={24} />
        </button>

        <div className="mb-8">
          <h2 className="text-3xl text-foreground mb-2">Welcome Back</h2>
          <p className="text-muted-foreground">Sign in to your account to continue</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label htmlFor="email" className="block text-foreground mb-2">
              Email Address
            </label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
              placeholder="your@email.com"
              required
            />
          </div>

          <div>
            <label htmlFor="password" className="block text-foreground mb-2">
              Password
            </label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
              placeholder="••••••••"
              required
            />
          </div>

          <button
            type="submit"
            className="w-full bg-gradient-to-r from-primary to-accent text-white py-4 rounded-xl hover:shadow-xl hover:scale-[1.02] transition-all"
          >
            Sign In
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-muted-foreground">
          Don't have an account?{' '}
          <button className="text-primary hover:underline">Sign up</button>
        </p>

        <div className="mt-4 p-3 bg-blue-50 rounded-xl border border-blue-200">
          <p className="text-xs text-blue-800 mb-1">Demo Login:</p>
          <p className="text-xs text-blue-700">
            <strong>Admin:</strong> admin@mototrack.com (any password)
          </p>
          <p className="text-xs text-blue-700">
            <strong>Customer:</strong> Any other email (any password)
          </p>
        </div>
      </div>
    </div>
  );
}
