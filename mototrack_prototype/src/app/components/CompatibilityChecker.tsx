import { Search, CheckCircle2, AlertCircle, Smartphone } from 'lucide-react';
import { useState } from 'react';

const motorcycleMakes = [
  'All Makes',
  'Honda',
  'Yamaha', 
  'Suzuki',
  'Kawasaki',
  'KTM',
  'BMW',
  'Ducati',
  'Harley-Davidson',
  'Royal Enfield'
];

const motorcycleModels: { [key: string]: string[] } = {
  'All Makes': ['Select a make first'],
  'Honda': ['Wave 110', 'Wave 125', 'Click 125', 'Click 150', 'Beat', 'TMX 155', 'XRM 125', 'CBR150R', 'ADV 150'],
  'Yamaha': ['Mio i 125', 'Mio Aerox', 'Mio Gravis', 'Sniper 150', 'NMAX', 'YZF-R15', 'MT-15', 'Sight'],
  'Suzuki': ['Raider 150', 'Smash 115', 'Address', 'Skydrive', 'GSX-R150', 'Gixxer'],
  'Kawasaki': ['Ninja 400', 'Z125 Pro', 'Versys-X 300', 'W175'],
  'KTM': ['Duke 200', 'Duke 390', 'RC 200', 'RC 390'],
  'BMW': ['G 310 R', 'G 310 GS', 'S 1000 RR'],
  'Ducati': ['Scrambler', 'Monster', 'Panigale V2'],
  'Harley-Davidson': ['Street 750', 'Iron 883', 'Sportster'],
  'Royal Enfield': ['Classic 350', 'Bullet 500', 'Himalayan']
};

export function CompatibilityChecker() {
  const [selectedMake, setSelectedMake] = useState('All Makes');
  const [selectedModel, setSelectedModel] = useState('Select Model');
  const [partName, setPartName] = useState('');
  const [checkResult, setCheckResult] = useState<'compatible' | 'not-compatible' | null>(null);

  const availableModels = motorcycleModels[selectedMake] || ['Select a make first'];

  const handleCheckCompatibility = () => {
    // Mock compatibility check
    if (selectedMake !== 'All Makes' && selectedModel !== 'Select Model' && partName) {
      // Randomly show compatible or not (in production, this would check a real database)
      const isCompatible = Math.random() > 0.3;
      setCheckResult(isCompatible ? 'compatible' : 'not-compatible');
    }
  };

  const handleMakeChange = (make: string) => {
    setSelectedMake(make);
    setSelectedModel('Select Model');
    setCheckResult(null);
  };

  return (
    <section id="compatibility" className="py-20 md:py-32 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 relative overflow-hidden">
      {/* Decorative elements */}
      <div className="absolute top-0 right-0 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
      <div className="absolute bottom-0 left-0 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-full mb-4 shadow-sm">
            <Search size={16} className="text-primary" />
            <span className="text-sm text-primary">Our Standout Feature</span>
          </div>
          <h2 className="text-3xl md:text-4xl lg:text-5xl text-foreground mb-6">
            Find the Right Part, Every Time
          </h2>
          <p className="text-lg md:text-xl text-muted-foreground max-w-3xl mx-auto">
            Check if a part is compatible with your motorcycle before you visit. 
            Save time and avoid unnecessary trips!
          </p>
        </div>

        <div className="max-w-4xl mx-auto">
          <div className="bg-white rounded-3xl shadow-2xl p-6 md:p-12 border border-border">
            <div className="mb-8">
              <h3 className="text-2xl text-foreground mb-2">Check Compatibility</h3>
              <p className="text-muted-foreground">
                Select your motorcycle make and model, then enter the part you're looking for
              </p>
            </div>

            <div className="space-y-6">
              <div className="grid md:grid-cols-2 gap-6">
                {/* Motorcycle Make */}
                <div>
                  <label htmlFor="motorcycleMake" className="block text-foreground mb-2">
                    Motorcycle Make
                  </label>
                  <select
                    id="motorcycleMake"
                    value={selectedMake}
                    onChange={(e) => handleMakeChange(e.target.value)}
                    className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors appearance-none cursor-pointer"
                  >
                    {motorcycleMakes.map((make) => (
                      <option key={make} value={make}>{make}</option>
                    ))}
                  </select>
                </div>

                {/* Model */}
                <div>
                  <label htmlFor="motorcycleModel" className="block text-foreground mb-2">
                    Model
                  </label>
                  <select
                    id="motorcycleModel"
                    value={selectedModel}
                    onChange={(e) => {
                      setSelectedModel(e.target.value);
                      setCheckResult(null);
                    }}
                    className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors appearance-none cursor-pointer"
                    disabled={selectedMake === 'All Makes'}
                  >
                    <option value="Select Model">Select Model</option>
                    {availableModels.map((model) => (
                      <option key={model} value={model}>{model}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Part Name */}
              <div>
                <label htmlFor="partName" className="block text-foreground mb-2">
                  Part Name
                </label>
                <input
                  type="text"
                  id="partName"
                  value={partName}
                  onChange={(e) => {
                    setPartName(e.target.value);
                    setCheckResult(null);
                  }}
                  placeholder="e.g., Brake pads, Air filter, Spark plug"
                  className="w-full px-4 py-3 border-2 border-border rounded-xl focus:outline-none focus:border-primary bg-input-background transition-colors"
                />
              </div>

              <button 
                onClick={handleCheckCompatibility}
                className="w-full bg-gradient-to-r from-primary to-accent text-white py-4 rounded-xl hover:shadow-xl hover:scale-[1.02] transition-all flex items-center justify-center gap-2 group"
              >
                <Search size={20} />
                Check Compatibility
                <CheckCircle2 size={20} className="group-hover:scale-110 transition-transform" />
              </button>

              {/* Compatibility Result */}
              {checkResult && (
                <div className={`p-6 rounded-2xl border-2 animate-in fade-in slide-in-from-top-4 duration-500 ${
                  checkResult === 'compatible' 
                    ? 'bg-green-50 border-green-300' 
                    : 'bg-red-50 border-red-300'
                }`}>
                  <div className="flex items-start gap-4">
                    {checkResult === 'compatible' ? (
                      <>
                        <div className="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                          <CheckCircle2 className="text-white" size={28} />
                        </div>
                        <div className="flex-1">
                          <h4 className="text-xl text-green-900 mb-2">Compatible!</h4>
                          <p className="text-green-700 mb-4">
                            Great news! This part is compatible with your {selectedMake} {selectedModel}.
                          </p>
                          <div className="bg-white p-4 rounded-xl border border-green-200">
                            <p className="text-sm text-green-900 mb-2">
                              <strong>Ready to purchase?</strong>
                            </p>
                            <p className="text-sm text-green-700">
                              Visit our shop during business hours (6AM-6PM daily) or check our Products catalog 
                              to see if it's in stock. Payment via GCash, pick up only.
                            </p>
                          </div>
                        </div>
                      </>
                    ) : (
                      <>
                        <div className="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                          <AlertCircle className="text-white" size={28} />
                        </div>
                        <div className="flex-1">
                          <h4 className="text-xl text-red-900 mb-2">Not Compatible</h4>
                          <p className="text-red-700 mb-4">
                            This part may not be compatible with your {selectedMake} {selectedModel}.
                          </p>
                          <div className="bg-white p-4 rounded-xl border border-red-200">
                            <p className="text-sm text-red-900 mb-2">
                              <strong>Need help finding the right part?</strong>
                            </p>
                            <p className="text-sm text-red-700">
                              Contact us or visit our shop. Our experts can help you find compatible alternatives 
                              for your motorcycle.
                            </p>
                          </div>
                        </div>
                      </>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Feature highlights */}
          <div className="mt-12 grid md:grid-cols-3 gap-6">
            <div className="bg-white p-6 rounded-2xl shadow-lg border border-border">
              <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                <svg className="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h4 className="text-lg text-foreground mb-2">Perfect Match</h4>
              <p className="text-muted-foreground text-sm">
                Our database helps you find the right part for your motorcycle model.
              </p>
            </div>

            <div className="bg-white p-6 rounded-2xl shadow-lg border border-border">
              <div className="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                <Smartphone className="w-6 h-6 text-blue-600" />
              </div>
              <h4 className="text-lg text-foreground mb-2">Check Anytime, Anywhere</h4>
              <p className="text-muted-foreground text-sm">
                Use our compatibility checker from home before making the trip to our shop.
              </p>
            </div>

            <div className="bg-white p-6 rounded-2xl shadow-lg border border-border">
              <div className="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                <svg className="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h4 className="text-lg text-foreground mb-2">Save Time</h4>
              <p className="text-muted-foreground text-sm">
                Avoid unnecessary trips by checking compatibility before visiting.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
