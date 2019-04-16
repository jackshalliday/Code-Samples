class PostsController < ApplicationController
  before_filter :authenticate_user!
  before_action :set_post, only: [:down_vote, :up_vote, :show, :edit, :update, :destroy, :flag]

  Post.reindex

  def index
    @posts = Post.page(params[:page]).per(5)
    @posts.sort_by { |post| -post.rank }
  end

  def show
  end

  def new
    @post = Post.new
  end

  def edit
  end

  def up_vote
    vote = Vote.new(from_node: current_user, to_node: @post, score: Vote::UP_SCORE)

    respond_to do |format|
      if vote.save
        format.js { render :index }
      else
        format.js {redirect_to post_path, notice: 'Error voting on post.'}
      end
    end
  end

  def down_vote
    vote = Vote.new(from_node: current_user, to_node: @post, score: Vote::DOWN_SCORE)

    respond_to do |format|
      if vote.save
        format.js { render :index }
      else
        format.js { redirect_to post_path, notice: 'Error voting on post.' }
      end
    end
  end

  def flag
      flag = Flag.new(from_node: current_user, to_node: @post, flag_type: 0)

      respond_to do |format|
        if flag.save
          format.js { render :index }
        else
          format.js { redirect_to post_path, notice: 'Error flagging on post.'}
        end
      end
  end

  def create
    @post = Post.new(post_params)
    current_user.posts << @post

    respond_to do |format|
      if @post.save
        format.html { redirect_to posts_path, notice: 'Post was successfully created.' }
      else
        format.html { render :new }
      end
    end
  end

  def update
    respond_to do |format|
      if @post.update(post_params)
        format.html { redirect_to @post, notice: 'Post was successfully updated.' }
      else
        format.html { render :edit }
      end
    end
  end

  def destroy
    @post.comments.each do |comment|
      comment.destroy
    end

    @post.destroy

    respond_to do |format|
      format.html { redirect_to posts_path, notice: 'Post was successfully destroyed.' }
    end
  end

  private

  def set_post
    @post = Post.find(params[:id])
  end

  def post_params
    params.require(:post).permit(:title, :content)
  end
end
