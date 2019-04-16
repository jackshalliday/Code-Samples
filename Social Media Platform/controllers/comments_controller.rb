class CommentsController < ApplicationController
  before_filter :authenticate_user!
  before_action :set_post, only: [:index, :show, :new, :edit, :create, :update, :destroy]
  before_action :set_comment, only: [:show, :edit, :update, :destroy]

  Comment.reindex

  def index
    @comments = @post.comments
  end

  def show
  end

  def new
    @comment = Comment.new
  end

  def edit
  end

  def create
    @comment = Comment.new(comment_params)
    @post.comments << @comment
    current_user.comments << @comment

    respond_to do |format|
      if @comment.save
        format.html { redirect_to @post , notice: 'Comment was successfully created.' }
      else
        format.html { render :new }
      end
    end
  end

  def update
    respond_to do |format|
      if @comment.update(comment_params)
        format.html { redirect_to [@post, @comment], notice: 'Comment was successfully updated.' }
      else
        format.html { render :edit }
      end
    end
  end

  def destroy
    @comment.destroy

    respond_to do |format|
      format.html { redirect_to @post, notice: 'Comment was successfully destroyed.' }
    end
  end

  private

  def set_post
    @post = Post.find(params[:post_id])
  end

  def set_comment
    @comment = @post.comments.find(params[:id])
  end

  def comment_params
    params.require(:comment).permit(:content)
  end
end
