class TagsController < ApplicationController
  before_filter :authenticate_user!
  before_action :set_tag, only: [:show, :edit, :update, :destroy]

  Tag.reindex

  def index
    @tags = Tag.page(params[:page]).per(25)
    @tags.sort_by { |tag| -tag.rank }
  end

  def show
  end

  def new
    @tag = Tag.new
  end

  def edit
  end

  def create
    @tag = Tag.new(tag_params)

    respond_to do |format|
      if @tag.save
        format.html { redirect_to tags_path, notice: 'Tag was successfully created.' }
      else
        format.html { render :new }
      end
    end
  end

  def update
    respond_to do |format|
      if @tag.update(tag_params)
        format.html { redirect_to @tag, notice: 'Tag was successfully updated.' }
      else
        format.html { render :edit }
      end
    end
  end

  def destroy
    posts = @tag.posts.select { |post| post.tags.count == 1 }
    posts.each :destroy

    @tag.destroy

    respond_to do |format|
      format.html { redirect_to tags_path, notice: 'Tag was successfully destroyed.' }
    end
  end

  private

  def set_tag
    @tag = Tag.find(params[:id])
  end

  def tag_params
    params.require(:tag).permit(:name)
  end
end