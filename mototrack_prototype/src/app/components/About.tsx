import { Award, Users, Clock, CheckCircle } from 'lucide-react';

export function About() {
  return (
    <section id="about" className="py-20 md:py-32 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Image/Visual side */}
          <div className="relative">
            <div className="aspect-[4/3] rounded-3xl overflow-hidden bg-gradient-to-br from-primary to-accent shadow-2xl flex items-center justify-center relative">
              <svg className="w-48 h-48 md:w-64 md:h-64 text-white opacity-30" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19,7H18V6A2,2 0 0,0 16,4H8A2,2 0 0,0 6,6V7H5A2,2 0 0,0 3,9V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V9A2,2 0 0,0 19,7M8,6H16V7H8V6M19,20H5V9H19V20Z"/>
              </svg>
              
              {/* Floating stats card */}
              <div className="absolute -bottom-6 -right-6 bg-white p-6 rounded-2xl shadow-2xl border border-border">
                <div className="text-center">
                  <div className="text-4xl text-primary mb-2">15+</div>
                  <div className="text-sm text-muted-foreground whitespace-nowrap">Years of Service</div>
                </div>
              </div>
            </div>
          </div>

          {/* Content side */}
          <div>
            <div className="inline-block bg-blue-100 text-primary px-4 py-2 rounded-full mb-4">
              About Us
            </div>
            <h2 className="text-3xl md:text-4xl lg:text-5xl text-foreground mb-6 leading-tight">
              Your Trusted Motorcycle Partner Since 2009
            </h2>
            <p className="text-lg text-muted-foreground mb-6 leading-relaxed">
              Edmar Motorcycle Parts and Accessories has been serving the Pandi, Bulacan community 
              for over 15 years. What started as a small shop has grown into a trusted destination 
              for motorcycle enthusiasts and everyday riders alike.
            </p>
            <p className="text-lg text-muted-foreground mb-8 leading-relaxed">
              We pride ourselves on offering quality parts, expert repair services, and personalized 
              customer care. Our team of experienced mechanics and staff are passionate about 
              motorcycles and dedicated to keeping your ride in top condition.
            </p>

            {/* Feature list */}
            <div className="grid sm:grid-cols-2 gap-4 mb-8">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Award className="text-primary" size={20} />
                </div>
                <div>
                  <h4 className="text-foreground mb-1">Quality Parts</h4>
                  <p className="text-sm text-muted-foreground">Genuine and reliable aftermarket options</p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Users className="text-purple-600" size={20} />
                </div>
                <div>
                  <h4 className="text-foreground mb-1">Expert Team</h4>
                  <p className="text-sm text-muted-foreground">Skilled mechanics you can trust</p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Clock className="text-pink-600" size={20} />
                </div>
                <div>
                  <h4 className="text-foreground mb-1">Fast Service</h4>
                  <p className="text-sm text-muted-foreground">Quick turnaround on repairs</p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <CheckCircle className="text-green-600" size={20} />
                </div>
                <div>
                  <h4 className="text-foreground mb-1">Fair Pricing</h4>
                  <p className="text-sm text-muted-foreground">Transparent and competitive rates</p>
                </div>
              </div>
            </div>

            <button 
              onClick={() => document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })}
              className="bg-gradient-to-r from-primary to-accent text-white px-8 py-4 rounded-xl hover:shadow-xl hover:scale-105 transition-all"
            >
              Visit Us Today
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
