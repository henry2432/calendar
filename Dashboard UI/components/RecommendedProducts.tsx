import React from 'react';
import { ArrowRight } from 'lucide-react';
import { Button } from './ui/button';
import ctaImage from 'figma:asset/e66338d0229cc81167292cd1bf04bf7cd3393bb9.png';

export function RecommendedProducts() {
  return (
    <section className="relative overflow-hidden">
      {/* Background Image */}
      <div className="relative h-[500px] md:h-[600px]">
        <img 
          src={ctaImage} 
          alt="Explore our products" 
          className="w-full h-full object-cover object-[center_20%]"
        />
        
        {/* Overlay Gradient */}
        <div className="absolute inset-0 bg-gradient-to-r from-black/60 via-black/40 to-transparent"></div>
        
        {/* Content */}
        <div className="absolute inset-0 flex items-center">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div className="max-w-xl">
              <h2 className="text-3xl md:text-5xl font-light text-white mb-4">
                探索更多
              </h2>
              <p className="text-lg md:text-xl text-white/90 mb-2">
                專為海洋而生的防曬衣系列
              </p>
              <p className="text-sm md:text-base text-white/80 mb-8">
                高性能面料 · UPF 50+ 防護 · 會員專屬優惠
              </p>
              
              <a 
                href="https://kayarine.club/%e5%93%81%e7%89%8c%e5%95%86%e5%ba%97/" 
                target="_blank" 
                rel="noopener noreferrer"
              >
                <Button 
                  size="lg" 
                  className="gap-2 bg-white text-black hover:bg-white/90 text-base px-8 py-6"
                >
                  查看全部商品
                  <ArrowRight className="h-5 w-5" />
                </Button>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}