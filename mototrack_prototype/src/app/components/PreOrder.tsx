import { CreditCard, Package, Clock, ArrowRight, Smartphone } from 'lucide-react';
import { useState } from 'react';

const motorcycleMakes = ['Select Make', 'Universal', 'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'KTM', 'BMW'];
const motorcycleModels = ['Select Model', 'Wave 110', 'Click 125', 'TMX 155', 'Mio', 'Raider 150', 'Sniper 150'];

export function PreOrder() {
  const [formData, setFormData] = useState({
    customerName: '',
    contactNumber: '',
    motorcycleMake: 'Select Make',
    motorcycleModel: 'Select Model',
    partName: '',
    quantity: 1,
    brandPreference: '',
    downPayment: '',
    notes: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission
    console.log('Pre-order submitted:', formData);
    alert('Pre-order request submitted! We will contact you via GCash for the 50% down payment.');
  };

  const handleReset = () => {
    setFormData({
      customerName: '',
      contactNumber: '',
      motorcycleMake: 'Select Make',
      motorcycleModel: 'Select Model',
      partName: '',
      quantity: 1,
      brandPreference: '',
      downPayment: '',
      notes: '',
    });
  };

  return (
    <section id="preorder" className="py-20 md:py-32 bg-gradient-to-br from-gray-50 via-white to-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <div className="inline-block bg-blue-100 text-primary px-4 py-2 rounded-full mb-4">
            Pre-Order System
          </div>
          <h2 className="text-3xl md:text-4xl lg:text-5xl text-foreground mb-6">
            Reserve Your Parts Today
          </h2>
          <p className="text-lg md:text-xl text-muted-foreground max-w-3xl mx-auto">
            Can't find what you need in stock? Pre-order with a 50% down payment and 
            we'll notify you when it arrives.
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 items-start max-w-6xl mx-auto">
          {/* Pre-Order Form */}
          <div className="bg-white rounded-3xl shadow-xl border border-border p-6 md:p-8">
            <h3 className="text-2xl text-foreground mb-6">Pre-Order Form</h3>
            
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Customer Name */}
              <div>
                <label htmlFor="customerName" className="block text-foreground mb-2">
                  Customer Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  id="customerName"
                  value={formData.customerName}
                  onChange={(e) => setFormData({ ...formData, customerName: e.target.value })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  placeholder="Enter your full name"
                  required
                />
              </div>

              {/* Contact Number */}
              <div>
                <label htmlFor="contactNumber" className="block text-foreground mb-2">
                  Contact Number <span className="text-red-500">*</span>
                </label>
                <input
                  type="tel"
                  id="contactNumber"
                  value={formData.contactNumber}
                  onChange={(e) => setFormData({ ...formData, contactNumber: e.target.value })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  placeholder="09XX XXX XXXX"
                  required
                />
              </div>

              {/* Motorcycle Make and Model */}
              <div className="grid sm:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="motorcycleMake" className="block text-foreground mb-2">
                    Motorcycle Make <span className="text-red-500">*</span>
                  </label>
                  <select
                    id="motorcycleMake"
                    value={formData.motorcycleMake}
                    onChange={(e) => setFormData({ ...formData, motorcycleMake: e.target.value })}
                    className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                    required
                  >
                    {motorcycleMakes.map((make) => (
                      <option key={make} value={make}>{make}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label htmlFor="motorcycleModel" className="block text-foreground mb-2">
                    Motorcycle Model <span className="text-red-500">*</span>
                  </label>
                  <select
                    id="motorcycleModel"
                    value={formData.motorcycleModel}
                    onChange={(e) => setFormData({ ...formData, motorcycleModel: e.target.value })}
                    className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                    required
                  >
                    {motorcycleModels.map((model) => (
                      <option key={model} value={model}>{model}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Part Name */}
              <div>
                <label htmlFor="partName" className="block text-foreground mb-2">
                  Part Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  id="partName"
                  value={formData.partName}
                  onChange={(e) => setFormData({ ...formData, partName: e.target.value })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  placeholder="E.g., Brake Pads, Air Filter, etc."
                  required
                />
              </div>

              {/* Quantity */}
              <div>
                <label htmlFor="quantity" className="block text-foreground mb-2">
                  Quantity <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  id="quantity"
                  min="1"
                  value={formData.quantity}
                  onChange={(e) => setFormData({ ...formData, quantity: parseInt(e.target.value) })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  required
                />
              </div>

              {/* Brand Preference */}
              <div>
                <label htmlFor="brandPreference" className="block text-foreground mb-2">
                  Brand Preference <span className="text-muted-foreground text-sm">(Optional)</span>
                </label>
                <input
                  type="text"
                  id="brandPreference"
                  value={formData.brandPreference}
                  onChange={(e) => setFormData({ ...formData, brandPreference: e.target.value })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                  placeholder="Specify brand if any"
                />
              </div>

              {/* Down Payment */}
              <div>
                <label htmlFor="downPayment" className="block text-foreground mb-2">
                  Down Payment (50% required) <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-4 top-1/2 transform -translate-y-1/2 text-muted-foreground">₱</span>
                  <input
                    type="number"
                    id="downPayment"
                    min="0"
                    step="0.01"
                    value={formData.downPayment}
                    onChange={(e) => setFormData({ ...formData, downPayment: e.target.value })}
                    className="w-full pl-8 pr-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                    placeholder="0.00"
                    required
                  />
                </div>
                <p className="text-xs text-muted-foreground mt-1">
                  Enter 50% of the estimated total price
                </p>
              </div>

              {/* Notes */}
              <div>
                <label htmlFor="notes" className="block text-foreground mb-2">
                  Notes / Additional Details <span className="text-muted-foreground text-sm">(Optional)</span>
                </label>
                <textarea
                  id="notes"
                  rows={4}
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors resize-none"
                  placeholder="Any special requirements or notes..."
                />
              </div>

              {/* Buttons */}
              <div className="flex flex-col sm:flex-row gap-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-gradient-to-r from-primary to-accent text-white py-4 rounded-xl hover:shadow-xl hover:scale-[1.02] transition-all"
                >
                  Submit Pre-Order
                </button>
                <button
                  type="button"
                  onClick={handleReset}
                  className="px-8 py-4 border-2 border-border text-muted-foreground rounded-xl hover:bg-gray-50 transition-all"
                >
                  Reset
                </button>
              </div>
            </form>
          </div>

          {/* How It Works & Payment Info */}
          <div className="space-y-6">
            {/* How It Works */}
            <div className="bg-gradient-to-br from-blue-50 to-purple-50 rounded-3xl shadow-lg border border-border p-6 md:p-8">
              <h3 className="text-2xl text-foreground mb-6">How It Works</h3>
              
              <div className="space-y-4 mb-8">
                <div className="flex gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-white flex-shrink-0 shadow-lg">
                    <Package size={24} />
                  </div>
                  <div>
                    <h4 className="text-foreground mb-1">1. Submit Pre-Order</h4>
                    <p className="text-sm text-muted-foreground">Fill out the form with your part details</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-white flex-shrink-0 shadow-lg">
                    <CreditCard size={24} />
                  </div>
                  <div>
                    <h4 className="text-foreground mb-1">2. Pay 50% Down Payment</h4>
                    <p className="text-sm text-muted-foreground">We'll send GCash payment details via SMS</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-white flex-shrink-0 shadow-lg">
                    <Clock size={24} />
                  </div>
                  <div>
                    <h4 className="text-foreground mb-1">3. We Process Your Order</h4>
                    <p className="text-sm text-muted-foreground">Your part will be ordered and reserved</p>
                  </div>
                </div>

                <div className="flex gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-white flex-shrink-0 shadow-lg">
                    <Smartphone size={24} />
                  </div>
                  <div>
                    <h4 className="text-foreground mb-1">4. Pick Up & Pay Balance</h4>
                    <p className="text-sm text-muted-foreground">Get notified when ready, pay remaining 50% on pickup</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Payment & Pickup Info */}
            <div className="bg-gradient-to-r from-primary via-purple-600 to-pink-600 text-white p-6 md:p-8 rounded-3xl shadow-xl">
              <h3 className="text-2xl mb-4">Payment & Pickup Information</h3>
              
              <div className="space-y-4 text-sm md:text-base">
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-white rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <strong>Pre-Order:</strong> 50% down payment required via GCash
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-white rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <strong>Available Products:</strong> 100% payment required for checkout
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-white rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <strong>Payment Method:</strong> GCash only (details sent via SMS)
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-white rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <strong>Delivery:</strong> Pick up only at our shop in Pandi, Bulacan
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-white rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-sm">✓</span>
                  </div>
                  <div>
                    <strong>Shop Hours:</strong> Open daily from 6:00 AM to 6:00 PM
                  </div>
                </div>
              </div>

              <div className="mt-6 p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                <p className="text-sm">
                  <strong>Note:</strong> After submitting your pre-order, we will contact you within 24 hours 
                  to confirm availability and provide GCash payment instructions.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
