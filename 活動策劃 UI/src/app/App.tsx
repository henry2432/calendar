import { Header } from "@/app/components/Header";
import { HeroSection } from "@/app/components/HeroSection";
import { TargetGroupsSection } from "@/app/components/TargetGroupsSection";
import { WhyKayarineSection } from "@/app/components/WhyKayarineSection";
import { CTASection } from "@/app/components/CTASection";
import { Footer } from "@/app/components/Footer";

export default function App() {
  return (
    <div className="min-h-screen">
      <Header />
      <main>
        <div id="home">
          <HeroSection />
        </div>
        <div id="target-groups">
          <TargetGroupsSection />
        </div>
        <div id="why-kayarine">
          <WhyKayarineSection />
        </div>
        <div id="contact">
          <CTASection />
        </div>
      </main>
      <Footer />
    </div>
  );
}
