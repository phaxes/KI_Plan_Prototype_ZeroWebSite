import React, { useState } from 'react';
import { motion } from 'motion/react';
import { Mail, Lock, ArrowRight, UserPlus, LogIn, Github, Chrome } from 'lucide-react';
import { cn } from '../lib/utils';

export default function Auth() {
  const [isLogin, setIsLogin] = useState(true);

  return (
    <div className="min-h-[80vh] flex items-center justify-center container mx-auto px-4 py-20">
      <div className="w-full max-w-md">
        <motion.div 
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          className="bg-white rounded-[40px] border border-neutral-100 p-8 shadow-2xl shadow-neutral-200/50"
        >
          <div className="text-center mb-10">
            <div className="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
              {isLogin ? <LogIn className="w-8 h-8 text-blue-600" /> : <UserPlus className="w-8 h-8 text-blue-600" />}
            </div>
            <h2 className="text-3xl font-bold mb-2">
              {isLogin ? 'Welcome Back' : 'Create Account'}
            </h2>
            <p className="text-neutral-400">
              Join our professional community.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-4 mb-8">
            <SocialBtn icon={<Chrome className="w-5 h-5" />} label="Google" />
            <SocialBtn icon={<Github className="w-5 h-5" />} label="GitHub" />
          </div>

          <div className="relative mb-8">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-neutral-100"></div>
            </div>
            <div className="relative flex justify-center text-xs uppercase tracking-widest">
              <span className="bg-white px-4 text-neutral-300">Or continue with mail</span>
            </div>
          </div>
          
          <form className="space-y-4" onSubmit={(e) => e.preventDefault()}>
            {!isLogin && (
              <div className="relative">
                <input 
                  type="text" 
                  placeholder="Full Name"
                  className="w-full px-5 py-4 rounded-2xl border border-neutral-100 bg-neutral-50/50 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/5 transition-all"
                />
              </div>
            )}
            <div className="relative">
              <Mail className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" />
              <input 
                type="email" 
                placeholder="Email address"
                className="w-full pl-14 pr-5 py-4 rounded-2xl border border-neutral-100 bg-neutral-50/50 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/5 transition-all"
              />
            </div>
            <div className="relative">
              <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" />
              <input 
                type="password" 
                placeholder="Password"
                className="w-full pl-14 pr-5 py-4 rounded-2xl border border-neutral-100 bg-neutral-50/50 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/5 transition-all"
              />
            </div>
            
            <button className="w-full py-5 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-all flex items-center justify-center gap-2 shadow-xl shadow-blue-100">
              {isLogin ? 'Sign In' : 'Register Now'} <ArrowRight className="w-5 h-5" />
            </button>
          </form>

          <div className="mt-8 text-center">
            <button 
              onClick={() => setIsLogin(!isLogin)}
              className="text-sm font-bold text-neutral-400 hover:text-blue-600 transition-colors"
            >
              {isLogin ? "Don't have an account? Sign Up" : "Already registered? Sign In"}
            </button>
          </div>
        </motion.div>
      </div>
    </div>
  );
}

function SocialBtn({ icon, label }: { icon: React.ReactNode, label: string }) {
  return (
    <button className="flex items-center justify-center gap-2 py-4 rounded-2xl border border-neutral-100 bg-neutral-50/50 hover:bg-white hover:shadow-lg hover:shadow-neutral-100 transition-all text-sm font-bold">
      {icon} {label}
    </button>
  );
}
