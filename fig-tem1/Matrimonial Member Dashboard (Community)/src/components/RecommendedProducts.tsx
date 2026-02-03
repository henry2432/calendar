import React from 'react';
import { ShoppingCart, Heart, Star } from 'lucide-react';
import { Card, CardContent } from './ui/card';
import { Button } from './ui/button';

export function RecommendedProducts() {
  const products = [
    {
      id: 1,
      name: 'Elegant One-Piece Swimsuit',
      nameCh: '優雅連身泳衣',
      price: '$128',
      originalPrice: '$160',
      rating: '4.9',
      reviews: 243,
      image: 'https://images.unsplash.com/photo-1630406440709-80170ed7dfbb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3b21lbiUyMGZhc2hpb24lMjBzd2ltc3VpdCUyMGVsZWdhbnR8ZW58MXx8fHwxNzY5OTYyNTYzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      inStock: true,
    },
    {
      id: 2,
      name: 'UV Protection Rashguard',
      nameCh: '專業防曬泳衣',
      price: '$98',
      originalPrice: '$125',
      rating: '4.8',
      reviews: 189,
      image: 'https://images.unsplash.com/photo-1575232707828-11f149efc281?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3b21lbiUyMHJhc2hndWFyZCUyMHN1biUyMHByb3RlY3Rpb258ZW58MXx8fHwxNzY5OTYyNTY0fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      inStock: true,
    },
    {
      id: 3,
      name: 'Classic Bikini Set',
      nameCh: '經典比基尼套裝',
      price: '$88',
      originalPrice: '$110',
      rating: '5.0',
      reviews: 312,
      image: 'https://images.unsplash.com/photo-1560660019-7625c7e27d91?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3b21lbiUyMGJpa2luaSUyMGJlYWNoJTIwZmFzaGlvbnxlbnwxfHx8fDE3Njk5NjI1NjR8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      inStock: true,
    },
    {
      id: 4,
      name: 'Athletic Swimsuit',
      nameCh: '運動型泳衣',
      price: '$115',
      originalPrice: '$145',
      rating: '4.7',
      reviews: 156,
      image: 'https://images.unsplash.com/photo-1624296841740-d7255c8508cc?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3b21lbiUyMG9uZSUyMHBpZWNlJTIwc3dpbXN1aXR8ZW58MXx8fHwxNzY5OTYyNTY0fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      inStock: false,
    },
  ];

  return (
    <section className="bg-black text-white py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-light mb-4">推薦購買</h2>
          <p className="text-gray-400 text-lg">專為海洋而生的時尚泳裝</p>
        </div>

        {/* Products Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {products.map((product) => (
            <div key={product.id} className="group">
              <div className="relative aspect-[3/4] overflow-hidden bg-gray-900 mb-4">
                <img 
                  src={product.image} 
                  alt={product.name} 
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                />
                {!product.inStock && (
                  <div className="absolute inset-0 bg-black/70 flex items-center justify-center">
                    <span className="text-white text-sm border border-white px-4 py-2">補貨中</span>
                  </div>
                )}
                <button className="absolute top-4 right-4 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <Heart className="h-5 w-5 text-black" />
                </button>
              </div>
              
              <div className="space-y-2">
                <h3 className="text-sm font-light">{product.nameCh}</h3>
                <p className="text-xs text-gray-500">{product.name}</p>
                
                <div className="flex items-center gap-2 text-xs">
                  <div className="flex items-center gap-1">
                    <Star className="h-3 w-3 fill-white text-white" />
                    <span>{product.rating}</span>
                  </div>
                  <span className="text-gray-500">({product.reviews})</span>
                </div>

                <div className="flex items-center gap-3 pt-2">
                  <span className="text-lg font-light">{product.price}</span>
                  <span className="text-sm text-gray-500 line-through">
                    {product.originalPrice}
                  </span>
                </div>

                <button 
                  className="w-full py-3 border border-white hover:bg-white hover:text-black transition-colors duration-300 text-sm mt-4 disabled:opacity-50 disabled:cursor-not-allowed"
                  disabled={!product.inStock}
                >
                  {product.inStock ? '加入購物車' : '補貨中'}
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Footer Note */}
        <div className="text-center mt-16 pt-8 border-t border-gray-800">
          <p className="text-sm text-gray-400 mb-6">
            所有商品均提供 UPF 50+ 防曬保護 · 會員享專屬優惠
          </p>
          <button className="text-sm border border-white px-8 py-3 hover:bg-white hover:text-black transition-colors duration-300">
            查看全部商品
          </button>
        </div>
      </div>
    </section>
  );
}