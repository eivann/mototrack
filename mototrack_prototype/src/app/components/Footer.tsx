export function Footer() {
  return (
    <footer className="bg-gradient-to-br from-primary via-blue-800 to-purple-900 text-white py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid md:grid-cols-4 gap-8 mb-12">
          <div className="md:col-span-2">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                <span className="text-primary text-2xl">M</span>
              </div>
              <div>
                <div className="text-2xl tracking-tight">MOTOTRACK</div>
                <div className="text-sm text-blue-200">Edmar Motorcycle Parts</div>
              </div>
            </div>
            <p className="text-blue-100 leading-relaxed mb-4">
              Your trusted partner for quality motorcycle parts and expert repair services 
              in Pandi, Bulacan since 2009.
            </p>
            <div className="text-sm text-blue-200">
              Open Every Day: 6:00 AM - 6:00 PM
            </div>
          </div>

          <div>
            <h4 className="mb-4 text-lg">Quick Links</h4>
            <ul className="space-y-3 text-blue-100">
              <li>
                <a href="#home" className="hover:text-white transition-colors hover:translate-x-1 inline-block">
                  Home
                </a>
              </li>
              <li>
                <a href="#about" className="hover:text-white transition-colors hover:translate-x-1 inline-block">
                  About
                </a>
              </li>
              <li>
                <a href="#products" className="hover:text-white transition-colors hover:translate-x-1 inline-block">
                  Products
                </a>
              </li>
              <li>
                <a href="#compatibility" className="hover:text-white transition-colors hover:translate-x-1 inline-block">
                  Compatibility
                </a>
              </li>
              <li>
                <a href="#preorder" className="hover:text-white transition-colors hover:translate-x-1 inline-block">
                  Pre-Order
                </a>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="mb-4 text-lg">Contact Us</h4>
            <ul className="space-y-3 text-blue-100 text-sm">
              <li>Pandi, Bulacan</li>
              <li>+63 XXX XXX XXXX</li>
              <li>contact@edmarmoto.ph</li>
            </ul>
          </div>
        </div>

        <div className="border-t border-blue-700 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-blue-100 text-sm">
          <p>
            &copy; {new Date().getFullYear()} Edmar Motorcycle Parts & Accessories. All rights reserved.
          </p>
          <p>
            Powered by MOTOTRACK
          </p>
        </div>
      </div>
    </footer>
  );
}