import { Header } from '../components/Header';
import { Hero } from '../components/Hero';
import { About } from '../components/About';
import { Services } from '../components/Services';
import { CompatibilityChecker } from '../components/CompatibilityChecker';
import { PreOrder } from '../components/PreOrder';
import { Contact } from '../components/Contact';
import { Footer } from '../components/Footer';

export function LandingPage() {
  return (
    <div className="min-h-screen bg-background">
      <Header />
      <main>
        <Hero />
        <About />
        <Services />
        <CompatibilityChecker />
        <PreOrder />
        <Contact />
      </main>
      <Footer />
    </div>
  );
}
