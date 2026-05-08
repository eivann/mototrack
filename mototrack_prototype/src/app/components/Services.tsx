import { Wrench, Package, Search, ClipboardCheck } from 'lucide-react';

const services = [
  {
    icon: Package,
    title: 'Quality Parts',
    description: 'Wide selection of genuine and aftermarket motorcycle parts for all major brands.',
    color: 'from-blue-500 to-blue-600',
  },
  {
    icon: Search,
    title: 'Compatibility Check',
    description: 'Instantly verify if a part fits your motorcycle before you buy or visit.',
    color: 'from-purple-500 to-purple-600',
  },
  {
    icon: Wrench,
    title: 'Expert Repairs',
    description: 'Professional repair and maintenance services from experienced mechanics.',
    color: 'from-pink-500 to-pink-600',
  },
  {
    icon: ClipboardCheck,
    title: 'Pre-Order Service',
    description: 'Reserve parts with down payment and get notified when they arrive.',
    color: 'from-indigo-500 to-indigo-600',
  },
];

export function Services() {
  return (
    <section id="services" className="py-20 md:py-32">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <div className="inline-block bg-blue-100 text-primary px-4 py-2 rounded-full mb-4">
            What We Offer
          </div>
          <h2 className="text-4xl md:text-5xl text-foreground mb-6">
            Everything You Need for Your Bike
          </h2>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            From finding the right parts to expert repairs, we've got you covered.
          </p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {services.map((service, index) => (
            <div
              key={index}
              className="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all border border-border hover:border-transparent hover:-translate-y-2"
            >
              <div className={`w-14 h-14 bg-gradient-to-br ${service.color} rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg`}>
                <service.icon className="text-white" size={28} />
              </div>
              <h3 className="text-xl text-foreground mb-3">{service.title}</h3>
              <p className="text-muted-foreground leading-relaxed">{service.description}</p>
            </div>
          ))}
        </div>

        {/* Stats bar */}
        <div className="mt-16 bg-gradient-to-r from-primary to-accent text-white p-8 md:p-12 rounded-3xl shadow-2xl">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-3xl mb-2">
                <svg className="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div className="text-sm opacity-90">Fast Service</div>
            </div>
            <div>
              <div className="text-3xl mb-2">
                <svg className="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="text-sm opacity-90">Quality Assured</div>
            </div>
            <div>
              <div className="text-3xl mb-2">
                <svg className="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div className="text-sm opacity-90">Expert Mechanics</div>
            </div>
            <div>
              <div className="text-3xl mb-2">
                <svg className="w-10 h-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="text-sm opacity-90">Fair Pricing</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}