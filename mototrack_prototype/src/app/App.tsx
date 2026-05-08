import { RouterProvider } from 'react-router';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import { router } from './routes';

export default function App() {
  return (
    <AuthProvider>
      <CartProvider>
        <RouterProvider router={router} />
      </CartProvider>
    </AuthProvider>
  );
}