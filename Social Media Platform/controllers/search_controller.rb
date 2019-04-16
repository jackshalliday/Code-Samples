class SearchController < ApplicationController
  before_filter :authenticate_user!

  def index
    phrase = params[:q]

    if phrase.nil?
      @posts = []
      @comments = []
    else
      @posts = Post.search phrase, operator: 'or'
      @comments = Comment.search phrase, operator: 'or'

      @posts.sort_by { |p| -p.rank }
    end
  end
end

