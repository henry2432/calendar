import React from 'react';

export function AppleSlogan() {
  return (
    <section className="py-24 md:py-32">
      <div className="max-w-4xl mx-auto px-4 text-center space-y-8">
        <h2 className="text-3xl md:text-5xl lg:text-6xl font-light tracking-tight">
          我們不只提供設備
        </h2>
        <p className="text-xl md:text-2xl lg:text-3xl text-muted-foreground font-light">
          更創造屬於你的海洋故事
        </p>
        <div className="pt-8">
          <p className="text-lg md:text-xl text-primary font-medium italic">
            "Go with the flow"
          </p>
        </div>
      </div>
    </section>
  );
}
