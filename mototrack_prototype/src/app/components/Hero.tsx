import { ChevronRight, Sparkles } from 'lucide-react';
import { useNavigate } from 'react-router';

export function Hero() {
  const navigate = useNavigate();

  const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section id="home" className="pt-32 pb-20 md:pt-40 md:pb-32 bg-gradient-to-br from-blue-50 via-white to-blue-50 relative overflow-hidden">
      {/* Decorative elements */}
      <div className="absolute top-20 right-10 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
      <div className="absolute top-40 left-10 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
      <div className="absolute -bottom-8 left-20 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="grid md:grid-cols-2 gap-12 items-center">
          <div>
            <div className="inline-flex items-center gap-2 bg-blue-100 text-primary px-4 py-2 rounded-full mb-6">
              <Sparkles size={16} />
              <span className="text-sm">Serving Pandi, Bulacan since 2009</span>
            </div>
            <h1 className="text-4xl md:text-5xl lg:text-6xl text-foreground mb-6 leading-tight">
              Your <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">Trusted Partner</span> for Motorcycle Parts
            </h1>
            <p className="text-lg md:text-xl text-muted-foreground mb-8 leading-relaxed">
              Find the perfect parts for your ride, check compatibility online, 
              and get expert repair services—all in one place.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <button
                onClick={() => navigate('/products')}
                className="bg-gradient-to-r from-primary to-accent text-white px-8 py-4 rounded-xl hover:shadow-xl hover:scale-105 transition-all flex items-center justify-center gap-2 group"
              >
                Browse Products
                <ChevronRight size={20} className="group-hover:translate-x-1 transition-transform" />
              </button>
              <button
                onClick={() => scrollToSection('compatibility')}
                className="border-2 border-primary text-primary px-8 py-4 rounded-xl hover:bg-blue-50 transition-all"
              >
                Check Compatibility
              </button>
            </div>
            
            <div className="mt-12 grid grid-cols-3 gap-6">
              <div>
                <div className="text-2xl md:text-3xl text-primary mb-1">15+</div>
                <div className="text-sm text-muted-foreground">Years Experience</div>
              </div>
              <div>
                <div className="text-2xl md:text-3xl text-primary mb-1">1000+</div>
                <div className="text-sm text-muted-foreground">Happy Customers</div>
              </div>
              <div>
                <div className="text-2xl md:text-3xl text-primary mb-1">Open Daily</div>
                <div className="text-sm text-muted-foreground">6AM - 6PM</div>
              </div>
            </div>
          </div>
          
          <div className="relative">
            <div className="aspect-square rounded-3xl overflow-hidden bg-gradient-to-br from-gray-200 to-gray-300 shadow-2xl flex items-center justify-center">
              <svg className="w-48 h-48 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M12,20c-4.41,0-8-3.59-8-8s3.59-8,8-8s8,3.59,8,8S16.41,20,12,20z M12,6c-3.31,0-6,2.69-6,6s2.69,6,6,6s6-2.69,6-6S15.31,6,12,6z M12,16c-2.21,0-4-1.79-4-4s1.79-4,4-4s4,1.79,4,4S14.21,16,12,16z"/>
              </svg>
            </div>
            {/* Floating badge */}
            <div className="absolute -bottom-6 -left-6 bg-white p-6 rounded-2xl shadow-xl border border-border">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                  <svg className="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div>
                  <div className="text-sm text-muted-foreground">Open Daily</div>
                  <div className="text-lg text-foreground">6AM - 6PM</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}