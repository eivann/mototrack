import { MapPin, Phone, Mail, Clock } from 'lucide-react';

export function Contact() {
  return (
    <section id="contact" className="py-20 md:py-32 bg-gradient-to-br from-blue-50 via-white to-purple-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <div className="inline-block bg-blue-100 text-primary px-4 py-2 rounded-full mb-4">
            Visit Us
          </div>
          <h2 className="text-4xl md:text-5xl text-foreground mb-6">
            Come See Us in Pandi
          </h2>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            Stop by our shop or get in touch. We're here to help with all your motorcycle needs!
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
          {/* Contact Info */}
          <div className="space-y-6">
            <div className="bg-white rounded-2xl shadow-xl p-8 border border-border hover:shadow-2xl transition-all">
              <div className="flex items-start gap-6">
                <div className="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                  <MapPin className="text-white" size={24} />
                </div>
                <div>
                  <h4 className="text-xl text-foreground mb-2">Our Location</h4>
                  <p className="text-muted-foreground leading-relaxed">
                    Edmar Motorcycle Parts & Accessories<br />
                    Pandi, Bulacan, Philippines
                  </p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-2xl shadow-xl p-8 border border-border hover:shadow-2xl transition-all">
              <div className="flex items-start gap-6">
                <div className="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                  <Phone className="text-white" size={24} />
                </div>
                <div>
                  <h4 className="text-xl text-foreground mb-2">Call Us</h4>
                  <p className="text-muted-foreground leading-relaxed">
                    +63 XXX XXX XXXX<br />
                    <span className="text-sm">Available during business hours</span>
                  </p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-2xl shadow-xl p-8 border border-border hover:shadow-2xl transition-all">
              <div className="flex items-start gap-6">
                <div className="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                  <Mail className="text-white" size={24} />
                </div>
                <div>
                  <h4 className="text-xl text-foreground mb-2">Email Us</h4>
                  <p className="text-muted-foreground leading-relaxed">
                    contact@edmarmoto.ph<br />
                    <span className="text-sm">We'll respond within 24 hours</span>
                  </p>
                </div>
              </div>
            </div>

            <div className="bg-gradient-to-br from-primary to-accent text-white p-8 rounded-2xl shadow-xl">
              <div className="flex items-start gap-4">
                <Clock className="flex-shrink-0 mt-1" size={24} />
                <div>
                  <h4 className="text-xl mb-3">Business Hours</h4>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center">
                      <span>Every Day</span>
                      <span className="bg-white/20 px-3 py-1 rounded-full text-sm">6:00 AM - 6:00 PM</span>
                    </div>
                  </div>
                  <p className="text-blue-100 text-sm mt-4">
                    Open daily to serve you better!
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Map placeholder */}
          <div className="bg-white rounded-3xl shadow-2xl border border-border overflow-hidden h-full min-h-[500px] flex items-center justify-center">
            <div className="text-center p-8">
              <div className="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <MapPin className="text-primary" size={40} />
              </div>
              <h3 className="text-2xl text-foreground mb-2">Visit Our Shop</h3>
              <p className="text-muted-foreground mb-6">
                Edmar Motorcycle Parts & Accessories<br />
                Pandi, Bulacan
              </p>
              <div className="bg-gradient-to-br from-blue-50 to-purple-50 p-6 rounded-xl">
                <p className="text-sm text-muted-foreground mb-2">Open Every Day</p>
                <p className="text-lg text-foreground">6:00 AM - 6:00 PM</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}